<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index\Worker;

use MakinaCorpus\Files\FileManager;
use MakinaCorpus\Files\Error\FileDoesNotExistError;
use MakinaCorpus\Files\Index\WorkerFileIndex;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Proceed to real file deletion.
 */
final class DeleteWorker implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private FileManager $fileManager;
    private WorkerFileIndex $fileIndex;
    private bool $stopOnError;

    public function __construct(FileManager $fileManager, WorkerFileIndex $fileIndex, bool $stopOnError = false)
    {
        $this->fileIndex = $fileIndex;
        $this->fileManager = $fileManager;
        $this->stopOnError = $stopOnError;
        $this->logger = new NullLogger();
    }

    /**
     * Run and return number of processed elements.
     */
    public function run(int $max = 0, bool $dryRun = false): int
    {
        // When running in dry-mode, since files will not really be deleted,
        // file index will always return something in findNextToDelete()
        // so we have to put a max value here to stop it.
        if ($dryRun) {
            $max = 1;
        }

        $done = 0;
        do {
            $nextInLine = $this->fileIndex->findNextToDelete();

            if (!$nextInLine) {
                break;
            }
            $done++;

            $filename = $nextInLine->getFile()->toString();

            try {
                $count = $this->fileIndex->countUsages($filename);

                // Only delete file if it's not in use.
                if ($count) {
                    $this->logger->notice(
                        "File '{file}' (with id '{id}') will not be deleted because {count} index rows uses it.",
                        [
                            'file' => $filename,
                            'id' => $nextInLine->getId()->toString(),
                            'count' => $count,
                        ]
                    );
                } else {
                    $this->logger->notice(
                        "File '{file}' (with id '{id}') will be deleted.",
                        [
                            'file' => $filename,
                            'id' => $nextInLine->getId()->toString(),
                        ]
                    );

                    if (!$dryRun) {
                        $this->fileManager->delete($filename);
                    }
                }

                if (!$dryRun) {
                    $this->fileIndex->reallyDeleteIndexedFile($nextInLine->getId());
                }
            } catch (FileDoesNotExistError $e) {
                // File was already deleted by something else, it's not an error
                // we can't handle: let the row be deleted from index and we're
                // good to go.
                $this->logger->warning(
                    "File '{file}' (with id '{id}') is already missing from file system.",
                    [
                        'file' => $filename,
                        'id' => $nextInLine->getId()->toString(),
                    ]
                );

                if (!$dryRun) {
                    $this->fileIndex->reallyDeleteIndexedFile($nextInLine->getId());
                }
            } catch (\Throwable $e) {
                $this->logger->error(
                    "File '{file}' (with id '{id}') could not be deleted with message: {error_message}, trace is: {error_trace}.",
                    [
                        'file' => $filename,
                        'id' => $nextInLine->getId()->toString(),
                        'error_message' => $e->getMessage(),
                        'error_trace' => $e->getTraceAsString(),
                    ]
                );

                if ($this->stopOnError) {
                    break;
                }
            }
        } while (!$max || $done < $max);

        return $done;
    }
}
