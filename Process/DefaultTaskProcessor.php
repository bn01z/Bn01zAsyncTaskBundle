<?php

namespace Bn01z\AsyncTask\Process;

use Bn01z\AsyncTask\Queue\Runnable;
use Bn01z\AsyncTask\Queue\TaskQueue;
use Bn01z\AsyncTask\Task\AsyncTaskException;
use Bn01z\AsyncTask\Task\TaskFactory;

final class DefaultTaskProcessor implements TaskProcessor
{
    /**
     * @var TaskQueue
     */
    private $queue;
    /**
     * @var TaskFactory
     */
    private $taskFactory;
    /**
     * @var ProcessManager
     */
    private $processManager;

    public function __construct(TaskQueue $queue, TaskFactory $taskFactory, ProcessManager $processManager)
    {
        $this->queue = $queue;
        $this->taskFactory = $taskFactory;
        $this->processManager = $processManager;
    }

    public function process(int $workerCount = 1): void
    {
        if ($workerCount <= 0) {
            throw new AsyncTaskException('Number of workers must be a positive number.');
        }
        for ($workerNumber = 1; $workerNumber <= $workerCount; ++$workerNumber) {
            $this->processManager->run([$this, 'runWorker']);
        }
        if ($this->queue instanceof Runnable) {
            $this->processManager->run([$this->queue, 'run']);
        }
        $this->processManager->wait();
    }

    /**
     * @codeCoverageIgnore
     */
    public function runWorker()
    {
        while (true) {
            $this->runTask();
        }
    }

    public function runTask(): bool
    {
        try {
            $request = $this->queue->getNext();
            $task = $this->taskFactory->createFromRequest($request);
            $task->execute();

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
