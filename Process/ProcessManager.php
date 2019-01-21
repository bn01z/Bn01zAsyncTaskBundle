<?php

namespace Bn01z\AsyncTask\Process;

use Bn01z\AsyncTask\Task\AsyncTaskException;

interface ProcessManager
{
    /**
     * @param callable $call
     * @throws AsyncTaskException
     */
    public function run(callable $call): void;
    public function wait(): void;
}