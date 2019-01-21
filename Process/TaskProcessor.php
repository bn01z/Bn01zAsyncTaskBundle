<?php

namespace Bn01z\AsyncTask\Process;

use Bn01z\AsyncTask\Task\AsyncTaskException;

interface TaskProcessor
{
    /**
     * @param int $workerCount
     * @throws AsyncTaskException
     */
    public function process(int $workerCount = 1): void;
}
