<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index;

final class ArrayAttributeBag implements AttributeBag
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute(string $name): ?string
    {
        return $this->data[$name] ?? null;
    }
}
