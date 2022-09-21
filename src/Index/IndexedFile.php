<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index;

use MakinaCorpus\Files\File;
use Ramsey\Uuid\UuidInterface;

interface IndexedFile
{
    /**
     * Get file identifier.
     */
    public function getId(): UuidInterface;

    /**
     * Get date at which file was indexed.
     */
    public function getIndexedAt(): \DateTimeInterface;

    /**
     * Is file marked for deletion in index.
     */
    public function isDeleted(): bool;

    /**
     * Deletion date.
     */
    public function getDeletedAt(): ?\DateTimeInterface;

    /**
     * Human readable label.
     */
    public function getDisplayLabel(): string;

    /**
     * Get all file attributes.
     *
     * @return array<string,string>
     *   Keys are attribute names, values are string values.
     */
    public function getAttributes(): array;

    /**
     * Get a single attribute value.
     */
    public function getAttribute(string $name): ?string;

    /**
     * Get associated file, most data will be restored from index and not
     * from real files, avoiding I/O manipulations at runtime. It could be
     * in desync thought, be aware of this.
     */
    public function getFile(): File;
}
