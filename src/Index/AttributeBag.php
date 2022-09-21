<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index;

/**
 * Interface for internal IndexedFile usage.
 *
 * It allows backends to make attribute fetching lazy.
 */
interface AttributeBag
{
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
}
