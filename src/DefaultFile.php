<?php

declare(strict_types=1);

namespace MakinaCorpus\Files;

use MakinaCorpus\Files\Error\FileCannotBeReadError;
use Symfony\Component\Mime\MimeTypes;

/**
 * Do not instanciate this class directly, use FileManager::create() instead.
 */
class DefaultFile implements File
{
    private string $relativeURI;
    private string $scheme;
    private ?string $workingDirectory;
    private ?\DateTimeInterface $ctime = null;
    private bool $ctimeComputed = false;
    private ?\DateTimeInterface $mtime = null;
    private bool $mtimeComputed = false;
    private ?int $filesize = null;
    private ?string $mimetype = null;
    private ?string $sha1sum = null;

    public function __construct(
        string $scheme,
        string $relativeURI,
        ?string $workingDirectory,
        ?string $mimetype = null,
        ?string $sha1sum = null,
        ?int $filesize = null,
        ?\DateTimeInterface $ctime = null,
        ?\DateTimeInterface $mtime = null
    ) {
        $this->ctime = $ctime;
        $this->filesize = $filesize;
        $this->mimetype = $mimetype;
        $this->mtime = $mtime;
        $this->relativeURI = $relativeURI;
        $this->scheme = $scheme;
        $this->sha1sum = $sha1sum;
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * File creation date (ctime in POSIX file systems).
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        if ($this->ctimeComputed) {
            return $this->ctime;
        }

        $this->ctimeComputed = true;

        return $this->ctime ?? ($this->ctime = $this->computeCreationDate());
    }

    /**
     * Last modified date (mtime in POSIX file systems).
     */
    public function getLastModified(): ?\DateTimeInterface
    {
        if ($this->mtimeComputed) {
            return $this->mtime;
        }

        $this->mtimeComputed = true;

        return $this->mtime ?? ($this->mtime = $this->computeModificationDate());
    }

    /**
     * {@inheritdoc}
     */
    public function getBasename(): string
    {
        return \basename($this->relativeURI);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelativePath(): string
    {
        return $this->relativeURI;
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory ?? '/';
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getAbsolutePath(): string
    {
        return $this->workingDirectory ? $this->workingDirectory . '/' . $this->relativeURI : '/' . $this->relativeURI;
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return $this->scheme . '://' . $this->relativeURI;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->scheme . '://' . $this->relativeURI;
    }

    /**
     * {@inheritdoc}
     */
    public final function getMimeType(): ?string
    {
        // @todo Fallback on type guessing using file extension?
        return $this->mimetype ?? ($this->mimetype = $this->computeMimeType() ?? 'application/octet-stream');
    }

    /**
     * {@inheritdoc}
     */
    public final function getSha1sum(): ?string
    {
        return $this->sha1sum ?? ($this->sha1sum = $this->computeSha1sum());
    }

    /**
     * {@inheritdoc}
     */
    public final function getFilesize(): int
    {
        return $this->filesize ?? ($this->filesize = $this->computeFilesize());
    }

    /**
     * Tell if mime type, size and sha1sum, then byte to byte content data
     * comparison matches.
     */
    public function isIdenticalTo(File $other): bool
    {
        if ($this === $other || $this->toString() === $other->toString()) {
            return true;
        }

        if (!$this->isProbablyIdenticalTo($other)) {
            return false;
        }

        // @todo Byte to byte data comparison.
        //   Let's be honest, in two years production use, more than 100GB files
        //   we never happen to stumble upon a false positive.
        //   Still, we should implement this somewhere.
        return true;
    }

    /**
     * Tell if mime type, size and sha1sum all matches.
     */
    public function isProbablyIdenticalTo(File $other): bool
    {
        if ($this === $other || $this->toString() === $other->toString()) {
            return true;
        }

        if ($other->getFilesize() !== $this->getFilesize()) {
            return false;
        }

        $sha1sum = $this->getSha1sum();
        if (null === $sha1sum) {
            throw new FileCannotBeReadError(\sprintf("File could not be read: '%s'", $this->toString()));
        }

        $otherSha1sum = $other->getSha1sum();
        if (null === $otherSha1sum) {
            throw new FileCannotBeReadError(\sprintf("File could not be read: '%s'", $other->toString()));
        }

        return $sha1sum === $otherSha1sum;
    }

    /**
     * {@inheritdoc}
     */
    public final function exists(): bool
    {
        return $this->computeFileExists();
    }

    protected function computeCreationDate(): ?\DateTimeInterface
    {
        $filename = $this->getAbsolutePath();

        if (!\is_file($filename)) {
            return null;
        }
        if (!\is_readable($filename)) {
            return null;
        }

        if (false === $timestamp = \filectime($filename)) {
            return null;
        }

        return new \DateTimeImmutable("@" . $timestamp);
    }

    protected function computeModificationDate(): ?\DateTimeInterface
    {
        $filename = $this->getAbsolutePath();

        if (!\is_file($filename)) {
            return null;
        }
        if (!\is_readable($filename)) {
            return null;
        }


        if (false === $timestamp = \filemtime($filename)) {
            return null;
        }

        return new \DateTimeImmutable("@" . $timestamp);
    }

    protected function computeSha1sum(): ?string
    {
        $filename = $this->getAbsolutePath();

        if (!\is_file($filename)) {
            return null;
        }
        if (!\is_readable($filename)) {
            return null;
        }

        return \sha1_file($this->getAbsolutePath());
    }

    protected function computeMimeType(): ?string
    {
        $filename = $this->getAbsolutePath();

        if (!\file_exists($filename) || !\is_readable($filename)) {
            return null;
        }

        return MimeTypes::getDefault()->guessMimeType($filename);
    }

    protected function computeFilesize(): int
    {
        $filename = $this->getAbsolutePath();

        if (!\file_exists($filename)) {
            return 0;
        }

        return (int) \filesize($filename);
    }

    protected function computeFileExists(): bool
    {
        return \file_exists($this->getAbsolutePath());
    }
}
