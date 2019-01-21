<?php

namespace Bn01z\AsyncTask\Status\Adapter;

use Bn01z\AsyncTask\Status\AbstractTaskStatusManager;
use Bn01z\AsyncTask\Status\TaskStatusException;
use Bn01z\AsyncTask\Status\WebSocketNotificator;
use Bn01z\AsyncTask\Task\TaskStatus;
use Predis\Client as RedisClient;

final class RedisTaskStatusManager extends AbstractTaskStatusManager
{
    /**
     * @var RedisClient Redis Client
     */
    private $redis;

    public function __construct($connection, WebSocketNotificator $notificator = null)
    {
        parent::__construct($notificator);
        $this->redis = new RedisClient($connection, ['prefix' => 'bn01z_async_task_status:']);
    }

    protected function getStatusData(string $identifier): string
    {
        if (!$data = $this->redis->get($identifier)) {
            throw new TaskStatusException('Status with specified ID not found.');
        }

        return $data;
    }

    protected function setStatusData(TaskStatus $status): void
    {
        $this->redis->set($status->getId(), serialize($status));
    }
}
