<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\FileSystem;

use MakinaCorpus\Files\File;

/**
 * File system basics for manipulating files.
 *
 * This API is poor voluntarily to be pluggable on most existing online
 * file systems, and be able to use the flysystem API as backend.
 *
 * It is not transactional, and will never be.
 *
 * @todo
 *  - file & directory mode 0755 0644
 *  - behaviour on move/copy (replace, rename)
 *  - use streams everywhere
 *  - make file manager implement this
 *  - use this interface with a decorator, with a registry which selects
 *    the right implementation depending upon the scheme
 *  - have a "is compatible with" or such methods, which allows stuff like
 *    using local file system mv/unlink/cp/... across instances
 *  - write a flysystem adapter
 *
 * Methods from FileManager to map:
 *
 * - copy()
 * - create() -> info()
 * - deduplicate()
 * - delete() -> deleteFile() | deleteDirectory()
 * - exists() -> exists()
 * - isDuplicateOf()
 * - ls() -> listDirectory()
 * - mkdir() -> createDirectory()
 * - rename() -> rename()
 * - renameIfNotWithin()
 */
interface FileSystem
{
    /**
     * Create directory.
     */
    public function createDirectory(string $path): void;

    /**
     * Delete directory.
     */
    public function deleteDirectory(string $path): void;

    /**
     * List all files in directory.
     */
    public function listDirectory(string $path): iterable;

    /**
     * Fetch file information, return.
     */
    public function info(string $path): File;

    /**
     * Fetch file information, return null if it does not exists.
     */
    public function exists(string $path): ?File;

    /**
     * Move file, creating missing directories along.
     */
    public function rename(string $path, string $newPath): File;

    /**
     * Read file contents and return it as a string.
     */
    public function read(string $path): string;

    /**
     * Open stream on file.
     */
    public function readStream(string $path) /* : resource */;

    /**
     * Write file contents using a data string.
     */
    public function write(string $path, string $contents): int;

    /**
     * Write file contents from given stream (will consume it).
     */
    public function writeStream(string $path, $resource): int;

    /**
     * Delete file.
     */
    public function delete(string $path): void;
}
