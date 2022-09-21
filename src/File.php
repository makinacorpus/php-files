<?php

declare(strict_types=1);

namespace MakinaCorpus\Files;

interface File extends FileIdentity
{
    /**
     * File creation date (ctime for POSIX file systems).
     */
    public function getCreatedAt(): ?\DateTimeInterface;

    /**
     * Last modified date (mtime for POSIX file systems).
     */
    public function getLastModified(): ?\DateTimeInterface;

    /**
     * Get MIME type if known, may return null in case of error.
     */
    public function getMimeType(): ?string;

    /**
     * Get a SHA1 summary of the file, if knonw, may return null in case of error.
     */
    public function getSha1sum(): ?string;

    /**
     * Get file size, in bytes.
     */
    public function getFilesize(): int;

    /**
     * Does file exists.
     */
    public function exists(): bool;

    /**
     * Tell if size, mimetype and sha1sum all matches.
     */
    public function isProbablyIdenticalTo(File $other): bool;
}
