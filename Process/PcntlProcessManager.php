<?php

namespace Bn01z\AsyncTask\Process;

use Bn01z\AsyncTask\Task\AsyncTaskException;

final class PcntlProcessManager implements ProcessManager
{
    public function run(callable $call): void
    {
        if ($this->fork()) {
            // @codeCoverageIgnoreStart
            $call();
            exit();
            // @codeCoverageIgnoreEnd
        }
    }

    public function wait(): void
    {
        pcntl_wait($status);
    }

    private function fork(): bool
    {
        try {
            return 0 === pcntl_fork();
        } catch (\Throwable $exception) {
            throw new AsyncTaskException('Could not start child process.', $exception);
        }
    }
}
