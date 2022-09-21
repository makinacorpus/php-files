<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index\Query;

class DefaultFileIndexQueryResult implements FileIndexQueryResult, \IteratorAggregate
{
    private int $count;
    private ?int $total;
    private int $page;
    private int $limit;
    private iterable $result;

    public function __construct(
        int $count,
        ?int $total,
        int $page,
        int $limit,
        iterable $result
    ) {
        $this->count = $count;
        $this->limit = $limit;
        $this->page = $page;
        $this->result = $result;
        $this->total = $total;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function total(): ?int
    {
        return $this->total;
    }

    /**
     * {@inheritdoc}
     */
    public function page(): int
    {
        return $this->page;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(): int
    {
        return $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return (fn () => yield from $this->result)();
    }
}
