<?php

namespace Bn01z\AsyncTask\Status\Adapter;

use Bn01z\AsyncTask\Status\AbstractTaskStatusManager;
use Bn01z\AsyncTask\Status\TaskStatusException;
use Bn01z\AsyncTask\Status\WebSocketNotificator;
use Bn01z\AsyncTask\Task\TaskStatus;

final class FileTaskStatusManager extends AbstractTaskStatusManager
{
    /**
     * @var string
     */
    private $filesLocation;

    public function __construct(string $saveDir, WebSocketNotificator $notificator = null)
    {
        parent::__construct($notificator);
        $this->filesLocation = $saveDir;
        if (!file_exists($this->filesLocation)) {
            mkdir($this->filesLocation, 0755, true);
        }
    }

    protected function getStatusData(string $identifier)
    {
        $filePath = $this->getFilePath($identifier);
        if (!is_file($filePath) || !is_readable($filePath) || !($data = file_get_contents($filePath))) {
            throw new TaskStatusException('Status with specified ID not found.');
        }

        return $data;
    }

    protected function setStatusData(TaskStatus $status): void
    {
        $filePath = $this->getFilePath($status->getId());
        if (false === @file_put_contents($filePath, serialize($status))) {
            throw new TaskStatusException(sprintf('Could not save status to file on path %s', $filePath));
        }
    }

    private function getFilePath($identifier): string
    {
        return sprintf('%s/%s', $this->filesLocation, $identifier);
    }
}
