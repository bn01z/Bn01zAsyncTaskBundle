<?php

namespace Bn01z\AsyncTask\Task;

use Bn01z\AsyncTask\Status\TaskStatusManager;

class TaskStatusHelper
{
    /**
     * @var TaskStatus
     */
    private $status;
    /**
     * @var TaskStatusManager
     */
    private $statusManager;

    public function __construct(TaskStatus $status, TaskStatusManager $statusManager)
    {
        $this->status = $status;
        $this->statusManager = $statusManager;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function toProcessing(?string $message = null): void
    {
        $this->statusManager->set(
            $this->status
                ->setStatus(TaskStatus::STATUS_PROCESSING)
                ->setMessage($message ?: 'processing')
        );
    }

    public function toFinished(?string $message = null): void
    {
        $this->statusManager->set(
            $this->status
                ->setStatus(TaskStatus::STATUS_FINISHED)
                ->setProgress(100)->setMessage($message ?: 'finished')
        );
    }

    public function toFailed(?string $message = null): void
    {
        $this->statusManager->set(
            $this->status
                ->setStatus(TaskStatus::STATUS_FAILED)
                ->setMessage($message ?: 'failed')
        );
    }

    public function setResult($result): void
    {
        $this->status->setResult($result);
    }
}
