<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index;

use Ramsey\Uuid\UuidInterface;

/**
 * File index for background workers.
 *
 * You should not use this interface in your business code and let the worker
 * do their job instead.
 *
 * For implementors, you should implement this interface on your custom file
 * index implementations.
 */
interface WorkerFileIndex
{
    /**
     * Find how many rows do use the same file URI.
     *
     * This only counts NON-deleted rows.
     */
    public function countUsages(string $uri): int;

    /**
     * Find a single instance.
     *
     * Method is the same a the FileIndex interface, so you can safely use both
     * interfaces and implement only once.
     */
    public function find(UuidInterface $id): ?IndexedFile;

    /**
     * Find next file to delete.
     *
     * This should block this row for reading by other workers, ideally it must
     * be able to have more than one instance running concurently.
     */
    public function findNextToDelete(): ?IndexedFile;

    /**
     * Really delete the indexed file row from index.
     */
    public function reallyDeleteIndexedFile(UuidInterface $id): void;
}
