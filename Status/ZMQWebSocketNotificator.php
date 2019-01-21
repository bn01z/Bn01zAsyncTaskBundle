<?php

namespace Bn01z\AsyncTask\Status;

use Bn01z\AsyncTask\Task\TaskStatus;
use React\EventLoop\LoopInterface;
use React\ZMQ\Context;
use ZMQ;
use ZMQContext;

final class ZMQWebSocketNotificator implements WebSocketNotificator
{
    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function notify(TaskStatus $status): void
    {
        try {
            $context = new ZMQContext();
            $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'bn01z_async_task_status');
            $socket->connect($this->connection);
            $socket->send(serialize($status));
            $socket->disconnect($this->connection);
        } catch (\ZMQSocketException $exception) {
            throw new TaskStatusException('Error sending status to web socket', $exception);
        }
    }

    public function listen(callable $callback, LoopInterface $loop): void
    {
        try {
            $context = new Context($loop);
            $pull = $context->getSocket(ZMQ::SOCKET_PULL);
            $pull->getWrappedSocket()->bind($this->connection);
            $pull->on('message', $callback);
        } catch (\ZMQSocketException $exception) {
            throw new TaskStatusException('Error creating pusher service', $exception);
        }
    }
}
