<?php

namespace Bn01z\AsyncTask\Command;

use Bn01z\AsyncTask\Process\ProcessManager;
use Bn01z\AsyncTask\Process\TaskProcessor;
use Bn01z\AsyncTask\Status\WebSocketServer;
use Bn01z\AsyncTask\Task\AsyncTaskException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunCommand extends Command
{
    protected static $defaultName = 'bn01z:async-call:run';
    /**
     * @var TaskProcessor
     */
    private $processor;
    /**
     * @var WebSocketServer|null
     */
    private $webSocket;
    /**
     * @var ProcessManager
     */
    private $processManager;

    public function __construct(
        TaskProcessor $processor,
        ProcessManager $processManager,
        WebSocketServer $webSocket = null,
        $name = null
    ) {
        parent::__construct($name);
        $this->processor = $processor;
        $this->webSocket = $webSocket;
        $this->processManager = $processManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Run queue processor for async http calls.')
            ->addOption('workers', 'w', InputOption::VALUE_OPTIONAL, 'Number of workers', '1')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputOutput = new SymfonyStyle($input, $output);
        $workerCount = $input->getOption('workers');
        $workerCount = is_numeric($workerCount) ? (int) $workerCount : 1;

        $this->runStatusServer($inputOutput);
        $this->runWorkerQueue($inputOutput, $workerCount);
        $this->processManager->wait();
    }


    private function runStatusServer(SymfonyStyle $inputOutput): void
    {
        if ($this->webSocket instanceof WebSocketServer) {
            try {
                $this->processManager->run([$this->webSocket, 'run']);
            } catch (AsyncTaskException $exception) {
                $inputOutput->error(sprintf('Status server: %s', $exception->getMessage()));
            }
        }
    }

    protected function runWorkerQueue(SymfonyStyle $inputOutput, int $workerCount): void
    {
        try {
            $this->processor->process($workerCount);
        } catch (AsyncTaskException $exception) {
            $inputOutput->error(sprintf('Queue processor: %s', $exception->getMessage()));
        }
    }
}
