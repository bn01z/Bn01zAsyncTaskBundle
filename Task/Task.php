<?php

namespace Bn01z\AsyncTask\Task;

final class Task
{
    /**
     * @var callable
     */
    private $controller;
    /**
     * @var array
     */
    private $arguments;
    /**
     * @var TaskStatusHelper
     */
    private $statusHelper;

    public function __construct(callable $controller, array $arguments, TaskStatusHelper $statusHelper)
    {
        $this->controller = $controller;
        $this->arguments = $arguments;
        $this->statusHelper = $statusHelper;
    }

    public function execute(): void
    {
        try {
            $this->statusHelper->toProcessing();
            $this->statusHelper->setResult(
                call_user_func_array($this->controller, $this->arguments)
            );
            $this->statusHelper->toFinished();
        } catch (\Throwable $exception) {
            $this->statusHelper->toFailed($exception->getMessage());
            throw new AsyncTaskException('Error executing task.', $exception);
        }
    }
}
