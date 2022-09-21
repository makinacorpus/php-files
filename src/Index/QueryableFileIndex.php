<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index;

use MakinaCorpus\Files\Index\Query\FileIndexQuery;

/**
 * File index that can be queried.
 */
interface QueryableFileIndex
{
    /**
     * Index new file.
     */
    public function query(): FileIndexQuery;

    /**
     * Get all known mimetypes.
     *
     * @return array<string,int>
     *   Keys are mimetypes, values are file count. File count might be an
     *   approximation, or 0 if it can't be approximated.
     */
    public function findAllMimeTypes(): iterable;
}
