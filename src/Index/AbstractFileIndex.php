<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index;

use MakinaCorpus\Files\File;
use MakinaCorpus\Files\FileManager;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * When implementing this abstract class, you should not need to manipulate the
 * file manager or normalize URI, all URI will be normalized prior to abstract
 * protected method calls.
 */
abstract class AbstractFileIndex implements FileIndex, WorkerFileIndex
{
    private FileManager $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Do insert file.
     *
     * @param array<string, string> $preparedAttributes
     *   Keys are attributes names, values are string values.
     */
    abstract protected function doIndex(UuidInterface $id, File $file, array $preparedAttributes): IndexedFile;

    /**
     * Do upsert and merge attributes.
     *
     * @param array<string, string> $upserted
     *   Keys are attributes names, values are string values.
     * @param string[] $deleted
     *   Values are attributes names.
     */
    abstract protected function doSetAttributes(UuidInterface $id, array $upserted, array $deleted): void;

    /**
     * Do rename a single file.
     */
    abstract protected function doRename(UuidInterface $id, string $normalizedNewUri): IndexedFile;

    /**
     * Do rename a set of files matching an URI.
     *
     * Files should be matched using both $rawUri and $normalizedUri.
     */
    abstract protected function doRenameFromUri(string $rawUri, string $normalizedUri, string $normalizedNewUri): void;

    /**
     * Do count usage of a given URI.
     *
     * Files should be matched using both $rawUri and $normalizedUri.
     */
    abstract protected function doCountUsages(string $rawUri, string $normalizedUri): int;

    /**
     * Do mark file for deletion.
     */
    abstract protected function doMarkForDeletion(UuidInterface $id): void;

    /**
     * Do mark files with the given URI for deletion.
     *
     * Files should be matched using both $rawUri and $normalizedUri.
     */
    abstract protected function doMarkForDeletionFromUri(string $rawUri, string $normalizedUri): void;

    /**
     * Do find files matching.
     *
     * Please refer to interface documentation for how matching should be done.
     *
     * @return IndexedFile[]
     */
    abstract protected function doFindMatching(File $file): iterable;

    /**
     * {@inheritdoc}
     */
    public final function index(File $file, ?array $attributes = null): IndexedFile
    {
        // We need attributes for populating return.
        [$attributes,] = $this->prepareAttributes($attributes ?? []);

        return $this->doIndex(Uuid::uuid4(), $file, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public final function indexFromUri(string $uri, ?array $attributes = null): IndexedFile
    {
        return $this->index($this->fileManager->create($uri), $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public final function setAttributes(UuidInterface $id, ?array $attributes): void
    {
        [$upserted, $deleted] = $this->prepareAttributes($attributes);

        $this->doSetAttributes($id, $upserted, $deleted);
    }

    /**
     * {@inheritdoc}
     */
    public final function rename(UuidInterface $id, string $newUri): IndexedFile
    {
        return $this->doRename($id, $this->normalize($newUri));
    }

    /**
     * {@inheritdoc}
     */
    public final function renameFromUri(string $uri, string $newUri): IndexedFile
    {
        return $this->doRenameFromUri($uri, $this->normalize($uri), $this->normalize($newUri));
    }

    /**
     * {@inheritdoc}
     */
    public final function delete(UuidInterface $id): void
    {
        $this->doMarkForDeletion($id);
    }

    /**
     * {@inheritdoc}
     */
    public final function deleteFromUri(string $uri): void
    {
        $this->doMarkForDeletionFromUri($uri, $this->normalize($uri));
    }

    /**
     * {@inheritdoc}
     */
    public final function countUsages(string $uri): int
    {
        return $this->doCountUsages($uri, $this->normalize($uri));
    }

    /**
     * {@inheritdoc}
     */
    public final function findMatching(File $file): iterable
    {
        return $this->doFindMatching($file);
    }

    /**
     * {@inheritdoc}
     */
    public final function findMatchingFromUri(string $uri): iterable
    {
        return $this->doFindMatching($this->fileManager->create($uri));
    }

    /**
     * Get file manager.
     */
    protected final function getFileManager(): FileManager
    {
        return $this->fileManager;
    }

    /**
     * Prepare attributes for merge.
     */
    protected final function prepareAttributes(array $attributes): array
    {
        $updated = [];
        $deleted = [];

        foreach ($attributes as $name => $value) {
            if (null === $value) {
                $deleted[] = $name;
            } else if (!\is_scalar($value) && !(\is_object($value) && \method_exists($value, '__toString'))) {
                throw new \InvalidArgumentException(\sprintf("Property value cannot be cast to string for: '%s'", $name));
            } else {
                $updated[$name] = (string) $value;
            }
        }

        return [$updated, $deleted];
    }

    /**
     * Normalize given URI.
     */
    protected final function normalize(string $uri): string
    {
        return $this->fileManager->normalizePath($uri);
    }
}
