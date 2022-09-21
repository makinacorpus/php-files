<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Bridge\Goat\Index;

use Goat\Query\ExpressionLike;
use Goat\Runner\Runner;
use MakinaCorpus\Files\Index\Query\AbstractFileIndexQuery;
use MakinaCorpus\Files\Index\Query\DefaultFileIndexQueryResult;
use MakinaCorpus\Files\Index\Query\FileIndexQueryResult;

final class GoatFileIndexQuery extends AbstractFileIndexQuery
{
    private GoatFileIndex $fileIndex;
    private Runner $runner;
    private string $filesTable;
    private string $attributesTable;

    public function __construct(
        GoatFileIndex $fileIndex,
        Runner $runner,
        string $filesTable ='file',
        string $attributesTable ='file_attribute'
    ) {
        $this->attributesTable = $attributesTable;
        $this->fileIndex = $fileIndex;
        $this->filesTable = $filesTable;
        $this->runner = $runner;
    }

    public function execute(): FileIndexQueryResult
    {
        $queryBuilder = $this->runner->getQueryBuilder();

        $select = $queryBuilder 
            ->select($this->filesTable, 'file')
            ->where('file.is_deleted', $this->deleted)
        ;

        $where = $select->getWhere();

        if ($this->createdAfter) {
            if ($this->createdBefore) {
                $where->isBetween('file.created_at', $this->createdAfter, $this->createdBefore);
            } else {
                $where->isGreaterOrEqual('file.created_at', $this->createdAfter);
            }
        } else if ($this->createdBefore) {
            $where->isLessOrEqual('file.created_at', $this->createdBefore);
        }

        if (null !== $this->minSize) {
            if (null !== $this->maxSize) {
                $where->isBetween('file.filesize', $this->minSize, $this->maxSize);
            } else {
                $where->isGreaterOrEqual('file.filesize', $this->minSize);
            }
        } else if (null !== $this->maxSize) {
            $where->isLessOrEqual('file.filesize', $this->maxSize);
        }

        if ($this->filenameContains) {
            $where->condition(ExpressionLike::iLike('file.filename', '%?%', $this->filenameContains));
        }

        $mimeTypeOr = $where->or();
        if ($this->mimeTypes) {
            $mimeTypeOr->condition('file.mimetype', $this->mimeTypes);
        }
        if ($this->mimeTypesMatches) {
            foreach ($this->mimeTypesMatches as $mimeTypeMatch) {
                $where->condition(ExpressionLike::iLike('file.mimetype', '%?%', $mimeTypeMatch));
            }
        }

        if ($this->attributes) {
            $index = 0;
            foreach ($this->attributes as $name => $values) {
                $tableAlias = 'file_attribute_' . (++$index);
                if ($values) {
                    $where->exists(
                        $queryBuilder
                            ->select($this->attributesTable, $tableAlias)
                            ->whereExpression($tableAlias . '.file_id = file.id')
                            ->where($tableAlias . '.name', $name)
                            ->where($tableAlias . '.value', $values)
                    );
                } else {
                    $where->exists(
                        $queryBuilder
                            ->select($this->attributesTable, $tableAlias)
                            ->whereExpression($tableAlias . '.file_id = file.id')
                            ->where($tableAlias . '.name', $name)
                    );
                }
            }
        }

        if ($this->limit) {
            $total = $select->getCountQuery()->execute()->fetchField();
            $result = $select
                ->range($this->limit, $this->limit * ($this->page - 1))
                ->setOption('hydrator', $this->fileIndex->hydrator())
                ->execute()
            ;
        } else {
            $result = $select
                ->setOption('hydrator', $this->fileIndex->hydrator())
                ->execute()
            ;
            $total = $result->countRows();
        }

        return new DefaultFileIndexQueryResult(
            $result->countRows(),
            $total,
            $this->limit ? $this->page : 1,
            $this->limit,
            $result
        );
    }
}
