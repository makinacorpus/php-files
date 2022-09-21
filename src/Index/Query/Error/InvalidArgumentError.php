<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index\Query\Error;

use MakinaCorpus\Files\Error\FileError;

/**
 * @codeCoverageIgnore
 */
class InvalidArgumentError extends \InvalidArgumentException implements FileError
{
}
