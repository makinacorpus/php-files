<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Error;

/**
 * @codeCoverageIgnore
 */
class FileAlreadyExistsError extends \InvalidArgumentException implements FileError
{
}
