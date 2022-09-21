<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index\Query;

/**
 * This object is an iterator of IndexedFile instances.
 */
interface FileIndexQueryResult extends \Traversable
{
    /**
     * Get current result count.
     */
    public function count(): int;

    /**
     * Get total number of matching results if backend allows it.
     */
    public function total(): ?int;

    /**
     * Get page that was sent to query.
     */
    public function page(): int;

    /**
     * Get limit that was sent to query.
     */
    public function limit(): int;
}
