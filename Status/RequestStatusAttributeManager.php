<?php

namespace Bn01z\AsyncTask\Status;

use Bn01z\AsyncTask\Task\TaskStatus;
use Symfony\Component\HttpFoundation\Request;

class RequestStatusAttributeManager
{
    private $statusAttributeName;

    public function __construct(string $statusAttributeName)
    {
        $this->statusAttributeName = $statusAttributeName;
    }

    public function set(Request $request, TaskStatus $status): void
    {
        $request->attributes->set($this->statusAttributeName, $status);
    }

    public function get(Request $request): TaskStatus
    {
        if (!$status = $request->attributes->get($this->statusAttributeName)) {
            throw new TaskStatusException('Task status is not found in the Request.');
        }

        return $status;
    }
}
