<?php

namespace Bn01z\AsyncTask\Status;

use Bn01z\AsyncTask\Task\TaskStatus;

abstract class AbstractTaskStatusManager implements TaskStatusManager
{
    /**
     * @var WebSocketNotificator|null
     */
    private $notificator;

    public function __construct(WebSocketNotificator $notificator = null)
    {
        $this->notificator = $notificator;
    }

    public function get(string $identifier): TaskStatus
    {
        try {
            return $this->createStatusFromData($this->getStatusData($identifier));
        } catch (\Throwable $exception) {
            throw new TaskStatusException('Error getting task status.', $exception);
        }
    }

    public function set(TaskStatus $status)
    {
        try {
            $this->setStatusData($status);
            $this->notifyWebSocket($status);
        } catch (\Throwable $exception) {
            throw new TaskStatusException('Error setting task status.', $exception);
        }
    }

    public function create(string $identifier): TaskStatus
    {
        $status = new TaskStatus($identifier);
        $status->setMessage('queued');
        $this->set($status);

        return $status;
    }

    private function createStatusFromData(string $data): TaskStatus
    {
        $status = @unserialize($data);
        if (!$status instanceof TaskStatus) {
            throw new TaskStatusException('Unserialized object is not task status.');
        }

        return $status;
    }

    private function notifyWebSocket(TaskStatus $status): void
    {
        if ($this->notificator instanceof WebSocketNotificator) {
            $this->notificator->notify($status);
        }
    }

    abstract protected function getStatusData(string $identifier);

    abstract protected function setStatusData(TaskStatus $status);
}
