<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Bridge\Symfony\Command;

use MakinaCorpus\Files\FileManager;
use MakinaCorpus\Files\Index\FileIndex;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class FileOrphanDeleteCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'files:delete:orphans';

    private FileIndex $fileIndex;
    private FileManager $fileManager;
    private bool $stopOnError;

    public function __construct(FileManager $fileManager, FileIndex $fileIndex)
    {
        parent::__construct();

        $this->fileIndex = $fileIndex;
        $this->fileManager = $fileManager;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription("Deletes orphaned files (files not in the index)");
        $this->addOption('no-dry-run', 'd', InputOption::VALUE_NONE, "Do not delete files, just output outdated file list.");
        $this->addOption('scheme', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "List of schemes to browse.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($noDryRun = (bool)$input->getOption('no-dry-run')) {
            $this->logger->warning('Not working in dry run (removing files)');
        } else {
            $this->logger->notice('Working in dry run (not removing files)');
        }

        $schemes = (array) $input->getOption('scheme');

        foreach ($this->fileManager->getKnownSchemes() as $scheme => $workingDirectory) {
            if ($schemes && !\in_array($scheme, $schemes)) {
                continue;
            }

            switch ($scheme) {
                case FileManager::SCHEME_PRIVATE:
                case FileManager::SCHEME_PUBLIC:
                case FileManager::SCHEME_TEMPORARY:
                case FileManager::SCHEME_UPLOAD:
                    $this->doHandleDirectory($input, $output, $scheme, $workingDirectory, !$noDryRun);
                    break;
                default:
                    $output->writeln(\sprintf("Skipping scheme '%s://' with directory '%s'", $scheme, $workingDirectory));
                    break;
            }
        }

        return 0;
    }

    /**
     * Handle file directory.
     */
    private function doHandleDirectory(InputInterface $input, OutputInterface $output, string $scheme, string $workingDirectory, bool $dryRun): int
    {
        $deleted = 0;

        // We need a recusrive directory iterator
        //    for each special dir
        //        for each file
        //            if not in index
        //                if dry_run
        //                    output name in table
        //                else
        //                    delete file
        foreach ($this->fileManager->lsRecursive($workingDirectory) as $splFile) {
            \assert($splFile instanceof \SplFileInfo);

            $file = $this->fileManager->create($splFile->getPathname());

            $found = false;
            foreach ($this->fileIndex->findMatching($file) as $found) {
                $found = true;
                break;
            }

            if ($found) {
                continue; // File OK.
            }

            $output->writeln(\sprintf("%s", $file->getAbsolutePath()));
        }

        return $deleted;
    }
}
