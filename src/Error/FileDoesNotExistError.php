<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Error;

/**
 * @codeCoverageIgnore
 */
class FileDoesNotExistError extends \InvalidArgumentException implements FileError
{
}
