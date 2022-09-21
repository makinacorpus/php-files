<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index;

use MakinaCorpus\Files\File;
use Ramsey\Uuid\UuidInterface;

/**
 * File index.
 *
 * This purely is an index, and doesn't manipulate files directly, it does
 * not create, move or delete them.
 *
 * By storing all files into an index, you can provide indexed read of your
 * business files, store metadata long them and query using those metadata,
 * index on those metadata.
 *
 * File deletion will only mark files for deletion in index, and an external
 * worker will do the real deletion job: this way, if your index manipulation
 * are embeded into a database (SQL in most cases) transaction, a file marked
 * for deletion will not be visible as such until transactions are fully commit:
 * this way irreversible data operations will never be done inside rollbacked
 * transactions.
 */
interface FileIndex
{
    /**
     * Index new file.
     */
    public function index(File $file, ?array $attributes = null): IndexedFile;

    /**
     * Index new file.
     */
    public function indexFromUri(string $uri, ?array $attributes = null): IndexedFile;

    /**
     * Set file attributes.
     *
     * @param array<string, string|null> $attributes
     *   Keys are attribute names, values are string values. Attributes not
     *   listed in this array but present in the database will be left as-is,
     *   explicit null values will delete the attribute.
     */
    public function setAttributes(UuidInterface $id, array $attributes): void;

    /**
     * Find a single instance.
     */
    public function find(UuidInterface $id): ?IndexedFile;

    /**
     * Move real file and rename indexed file.
     *
     * This method changes the indexed file target URI, but does not change
     * anything in the filesystem. If target file does not exists, computed
     * properties such as SHA1 summary or mimetype will be erroneous.
     */
    public function rename(UuidInterface $id, string $newUri): IndexedFile;

    /**
     * Move real file and rename indexed file.
     *
     * This targets files using an URI, so every matching index row will be
     * changed as well.
     *
     * This method changes the indexed file target URI, but does not change
     * anything in the filesystem. If target file does not exists, computed
     * properties such as SHA1 summary or mimetype will be erroneous.
     */
    public function renameFromUri(string $uri, string $newUri): IndexedFile;

    /**
     * Mark the file for deletion, do not really delete the file.
     *
     * Real file deletion is done by a cron task.
     *
     * If more than one rows exist with the same URI, real physical file will
     * not be deleted until all rows with the same URI are marked for deletion.
     */
    public function delete(UuidInterface $id): void;

    /**
     * Really delete the indexed file from index.
     *
     * DO NOT CALL THIS IN YOUR BUSINESS CODE. Let the worker do it for you.
     */
    public function reallyDeleteIndexedFile(UuidInterface $id): void;

    /**
     * Mark the file for deletion, do not really delete the file.
     *
     * If file exists on the filesystem, but not in index, it will be created
     * in index for later deletion.
     *
     * Real file deletion is done by a cron task.
     *
     * If more than one rows exist with the same URI, real physical file will
     * not be deleted until all rows with the same URI are marked for deletion.
     */
    public function deleteFromUri(string $uri): void;

    /**
     * Find how many rows do use the same file URI.
     *
     * This only counts NON-deleted rows.
     */
    public function countUsages(string $uri): int;

    /**
     * Find a matching/duplicate file.
     *
     * Maching means that it has a high probability to be the exact same file,
     * but it might not be (same size, mimetype and sha1sum).
     *
     * @return IndexedFile[]
     */
    public function findMatching(File $file): iterable;

    /**
     * Find a matching/duplicate file.
     *
     * Maching means that it has a high probability to be the exact same file,
     * but it might not be (same size, mimetype and sha1sum).
     *
     * @return IndexedFile[]
     */
    public function findMatchingFromUri(string $uri): iterable;
}
