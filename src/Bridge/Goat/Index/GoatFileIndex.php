<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Bridge\Goat\Index;

use Goat\Query\Query;
use Goat\Runner\Runner;
use MakinaCorpus\Files\DefaultFile;
use MakinaCorpus\Files\File;
use MakinaCorpus\Files\FileManager;
use MakinaCorpus\Files\Index\AbstractFileIndex;
use MakinaCorpus\Files\Index\ArrayAttributeBag;
use MakinaCorpus\Files\Index\AttributeBag;
use MakinaCorpus\Files\Index\CallbackAttributeBag;
use MakinaCorpus\Files\Index\DefaultIndexedFile;
use MakinaCorpus\Files\Index\IndexedFile;
use MakinaCorpus\Files\Index\QueryableFileIndex;
use MakinaCorpus\Files\Index\Query\FileIndexQuery;
use Ramsey\Uuid\UuidInterface;

final class GoatFileIndex extends AbstractFileIndex implements QueryableFileIndex
{
    private Runner $runner;
    private string $filesTable;
    private string $attributesTable;

    public function __construct(
        FileManager $fileManager,
        Runner $runner,
        string $filesTable ='file',
        string $attributesTable ='file_attribute'
    ) {
        parent::__construct($fileManager);

        $this->attributesTable = $attributesTable;
        $this->filesTable = $filesTable;
        $this->runner = $runner;
    }

    /**
     * {@inheritdoc}
     */
    protected function doIndex(UuidInterface $id, File $file, array $preparedAttributes): IndexedFile
    {
        $ret = $this
            ->runner
            ->getQueryBuilder()
            ->insert($this->filesTable)
            ->values([
                'created_at' => $file->getCreatedAt(),
                'deleted_at' => null,
                'expires_at' => null, // @todo unused for now.
                'filename' => $file->toString(),
                'filesize' => $file->getFilesize(),
                'id' => $id,
                'indexed_at' => new \DateTimeImmutable(),
                'is_anonymized' => false,
                'is_deleted' => false,
                'is_valid' => true,
                'mimetype' => $file->getMimeType() ?? 'application/octet-stream',
                'modified_at' => $file->getLastModified(),
                'name' => $file->getBasename(), // @todo there is no other way to set it.
                'sha1sum' => $file->getSha1sum(),
                'type' => 'file', // @todo unused for now (heritage from a legacy project).
            ])
            ->returning('*')
            ->setOption('hydrator', $this->hydrator($preparedAttributes))
            ->execute()
            ->fetch()
        ;

        $this->doSetAttributes($id, $preparedAttributes, []);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSetAttributes(UuidInterface $id, array $upserted, array $deleted): void
    {
        if ($upserted) {
            $query = $this
                ->runner
                ->getQueryBuilder()
                ->merge($this->attributesTable)
                ->setKey(['file_id', 'name'])
                ->onConflictUpdate()
                ->columns(['file_id', 'name', 'value'])
            ;
            foreach ($upserted as $name => $value) {
                $query->values([$id, $name, $value]);
            }
            $query->perform();
        }

        if ($deleted) {
            $query = $this
                ->runner
                ->getQueryBuilder()
                ->delete($this->attributesTable)
                ->where('file_id', $id)
                ->where('name', $deleted)
                ->perform()
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doRename(UuidInterface $id, string $normalizedNewUri): IndexedFile
    {
        throw new \Exception("Not implemented.");
    }

    /**
     * {@inheritdoc}
     */
    protected function doRenameFromUri(string $rawUri, string $normalizedUri, string $normalizedNewUri): void
    {
        throw new \Exception("Not implemented.");
    }

    /**
     * {@inheritdoc}
     */
    protected function doCountUsages(string $rawUri, string $normalizedUri): int
    {
        return (int) $this
            ->runner
            ->getQueryBuilder()
            ->select($this->filesTable)
            ->columnExpression('count(*)', 'count')
            ->where('filename', [$rawUri, $normalizedUri])
            ->where('is_deleted', false)
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function doMarkForDeletion(UuidInterface $id): void
    {
        $this
            ->runner
            ->getQueryBuilder()
            ->update($this->filesTable)
            ->set('is_deleted', true)
            ->set('deleted_at', new \DateTimeImmutable())
            ->where('id', $id)
            ->where('is_deleted', false)
            ->perform()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function doMarkForDeletionFromUri(string $rawUri, string $normalizedUri): void
    {
        $this
            ->runner
            ->getQueryBuilder()
            ->update($this->filesTable)
            ->set('is_deleted', true)
            ->set('deleted_at', new \DateTimeImmutable())
            ->where('filename', [$rawUri, $normalizedUri])
            ->where('is_deleted', false)
            ->perform()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFindMatching(File $file): iterable
    {
        if (!$sha1sum = $file->getSha1sum()) {
            return [];
        }

        return $this
            ->runner
            ->getQueryBuilder()
            ->select($this->filesTable)
            ->where('filesize', $file->getFilesize())
            ->where('mimetype', $file->getMimeType())
            ->where('sha1sum', $sha1sum)
            ->setOption('hydrator', $this->hydrator())
            ->range(1, 0)
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function find(UuidInterface $id): ?IndexedFile
    {
        return $this
            ->runner
            ->getQueryBuilder()
            ->select($this->filesTable)
            ->where('id', $id)
            ->setOption('hydrator', $this->hydrator())
            ->range(1, 0)
            ->execute()
            ->fetch()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function query(): FileIndexQuery
    {
        return new GoatFileIndexQuery($this, $this->runner, $this->filesTable, $this->attributesTable);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllMimeTypes(): iterable
    {
        return $this
            ->runner
            ->getQueryBuilder()
            ->select($this->filesTable)
            ->column('mimetype')
            ->columnExpression('count(mimetype)', 'count')
            ->orderBy('mimetype', Query::ORDER_ASC)
            ->groupBy('mimetype')
            ->execute()
            ->setKeyColumn('mimetype')
            ->fetchColumn('count')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function findNextToDelete(): ?IndexedFile
    {
        // @todo Use a WITH statement instead, then SELECT by random in the
        //   WITH result to randomize order in a subset of less than 100 items,
        //   meaning the SELECT query will still remain fast enough, but
        //   randomize enough to avoid most of workers walking on each other.
        return $this
            ->runner
            ->getQueryBuilder()
            ->select($this->filesTable)
            ->where('is_deleted', true)
            ->orderBy('deleted_at', Query::ORDER_ASC)
            ->range(1, 0)
            ->setOption('hydrator', $this->hydrator())
            ->execute()
            ->fetch()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function reallyDeleteIndexedFile(UuidInterface $id): void
    {
        $this
            ->runner
            ->getQueryBuilder()
            ->delete($this->filesTable)
            ->where('id', $id)
            ->perform()
        ;
    }

    /**
     * {@internal}
     */
    public function attributes(UuidInterface $id): AttributeBag
    {
        return new CallbackAttributeBag(
            fn () => $this
                ->runner
                ->getQueryBuilder()
                ->select($this->attributesTable)
                ->columns(['value', 'name'])
                ->where('file_id', $id)
                ->orderBy('name')
                ->execute()
                ->setKeyColumn('name')
                ->fetchColumn('value')
        );
    }

    /**
     * {@internal}
     */
    public function hydrator(?array $attributes = null): callable
    {
        return function (array $row) use ($attributes) {
            [$scheme, $relativeURI, $workingDirectory] = $this->getFileManager()->getUriComponents($row['filename']);

            return new DefaultIndexedFile(
                $row['id'],
                new DefaultFile(
                    $scheme,
                    $relativeURI,
                    $workingDirectory,
                    $row['mimetype'],
                    $row['sha1sum'],
                    $row['filesize'],
                    $row['created_at'],
                    $row['modified_at']
                ),
                $row['indexed_at'],
                $row['is_deleted'],
                $row['deleted_at'],
                $row['name'],
                ($attributes ? new ArrayAttributeBag($attributes) : $this->attributes($row['id']))
            );
        };
    }
}
