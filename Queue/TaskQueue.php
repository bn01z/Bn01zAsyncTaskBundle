<?php

namespace Bn01z\AsyncTask\Queue;

use Bn01z\AsyncTask\Status\TaskStatusException;
use Symfony\Component\HttpFoundation\Request;

interface TaskQueue
{
    /**
     * @param Request $request
     * @throws TaskStatusException
     */
    public function add(Request $request): void;

    /**
     * @return Request
     * @throws TaskStatusException
     */
    public function getNext(): Request;
}
