<?php

namespace Bn01z\AsyncTask\Task;

class AsyncTaskException extends \RuntimeException
{
    public function __construct(string $message = '', \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
