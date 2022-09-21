<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index;

use MakinaCorpus\Files\DefaultFile;
use MakinaCorpus\Files\File;
use Ramsey\Uuid\UuidInterface;

/**
 * This implementation is suitable for most storage implementations.
 *
 * Both File and AttributeBag properties can be lazy proxies.
 */
final class DefaultIndexedFile extends DefaultFile implements IndexedFile
{
    private UuidInterface $id;
    private File $file;
    private \DateTimeInterface $indexedAt;
    private bool $deleted = false;
    private ?\DateTimeInterface $deletedAt = null;
    private ?string $label = null;
    private AttributeBag $attributes;

    public function  __construct(
        UuidInterface $id,
        File $file,
        \DateTimeInterface $indexedAt,
        bool $deleted,
        ?\DateTimeInterface $deletedAt = null,
        ?string $label = null,
        ?AttributeBag $attributes = null
    ) {
        $this->id = $id;
        $this->file = $file;
        $this->indexedAt = $indexedAt;
        $this->deleted = $deleted;
        $this->deletedAt = $deletedAt;
        $this->label = $label;
        $this->attributes = $attributes ?? new ArrayAttributeBag([]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexedAt(): \DateTimeInterface
    {
        return $this->indexedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayLabel(): string
    {
        return $this->label ?? $this->file->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes->getAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute(string $name): ?string
    {
        return $this->attributes->getAttribute($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getFile(): File
    {
        return $this->file;
    }
}
