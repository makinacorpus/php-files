<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Bridge\Symfony\Command;

use MakinaCorpus\Files\FileManager;
use MakinaCorpus\Files\Index\FileIndex;
use MakinaCorpus\Files\Index\WorkerFileIndex;
use MakinaCorpus\Files\Index\Worker\DeleteWorker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class FileIndexDeleteCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'files:delete';

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
        $this->setDescription("Really delete files marked for deletion");
        $this->addOption('stop-on-error', null, InputOption::VALUE_NONE, "Stop on error.");
        $this->addOption('no-dry-run', 'd', InputOption::VALUE_NONE, "Do really delete files, otherwise just output outdated file list.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->fileIndex instanceof WorkerFileIndex) {
            throw new \Exception("File index does not implement worker capability.");
        }

        if ($noDryRun = (bool)$input->getOption('no-dry-run')) {
            $this->logger->warning('Not working in dry run (removing files)');
        } else {
            $this->logger->notice('Working in dry run (not removing files)');
        }
        $stopOnError = (bool)$input->getOption('stop-on-error');

        $worker = new DeleteWorker($this->fileManager, $this->fileIndex, $stopOnError);
        $worker->setLogger($this->logger);
 
        $worker->run(0, !$noDryRun);

        return 0;
    }
}
