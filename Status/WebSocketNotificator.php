<?php

namespace Bn01z\AsyncTask\Status;

use Bn01z\AsyncTask\Task\TaskStatus;
use React\EventLoop\LoopInterface;

interface WebSocketNotificator
{
    public function notify(TaskStatus $status): void;

    public function listen(callable $callback, LoopInterface $loop): void;
}
