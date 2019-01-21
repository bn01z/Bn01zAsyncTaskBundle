<?php

namespace Bn01z\AsyncTask\Queue\Adapter;

use Bn01z\AsyncTask\Queue\TaskQueue;
use Bn01z\AsyncTask\Queue\TaskQueueException;
use Predis\Client as RedisClient;
use Symfony\Component\HttpFoundation\Request;

final class RedisTaskQueue implements TaskQueue
{
    /**
     * @var RedisClient Redis client
     */
    private $redis;
    /**
     * @var string Worker queue name
     */
    private $queueName;

    public function __construct($connection, string $queueName)
    {
        $this->redis = new RedisClient($connection, ['prefix' => 'bn01z_async_task:']);
        $this->queueName = $queueName;
    }

    public function add(Request $request): void
    {
        try {
            if ($request->hasSession()) { // Fix to "unwrap" session closure which prevents serialization
                $request->getSession();
            }
            $this->redis->rpush($this->queueName, [serialize($request)]);
        } catch (\Throwable $exception) {
            throw new TaskQueueException('Error adding Request to Redis queue.', $exception);
        }
    }

    public function getNext(): Request
    {
        try {
            return unserialize($this->redis->blpop([$this->queueName], 0)[1]);
        } catch (\Throwable $exception) {
            throw new TaskQueueException('Error retrieving Request from Redis queue.', $exception);
        }
    }
}
