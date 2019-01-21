<?php

namespace Bn01z\AsyncTask\Status;

use Bn01z\AsyncTask\Task\TaskStatus;

interface TaskStatusManager
{
    /**
     * @param string $identifier
     * @return TaskStatus
     * @throws TaskStatusException
     */
    public function create(string $identifier): TaskStatus;

    /**
     * @param string $identifier
     * @return TaskStatus
     * @throws TaskStatusException
     */
    public function get(string $identifier): TaskStatus;

    /**
     * @param TaskStatus $status
     * @return mixed
     * @throws TaskStatusException
     */
    public function set(TaskStatus $status);
}
