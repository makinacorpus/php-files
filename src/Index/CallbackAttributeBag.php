<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index;

final class CallbackAttributeBag implements AttributeBag
{
    private ?array $data = null;
    /** @var null|callable */
    private $callback = null;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        $this->initialize();

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute(string $name): ?string
    {
        $this->initialize();

        return $this->data[$name] ?? null;
    }

    private function initialize(): void
    {
        if (null !== $this->data) {
            return;
        }

        $data = ($this->callback)();

        if (!\is_array($data)) {
            throw new \LogicException("Callback did not return an array");
        }

        $this->callback = null;
        $this->data = $data;
    }
}
