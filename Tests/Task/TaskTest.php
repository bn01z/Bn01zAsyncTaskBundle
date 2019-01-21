<?php

namespace Bn01z\AsyncTask\Tests\Task;

use Bn01z\AsyncTask\Task\AsyncTaskException;
use Bn01z\AsyncTask\Task\Task;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Task\TaskStatusHelper;
use Bn01z\AsyncTask\Tests\TestCase;

class TaskTest extends TestCase
{
    /**
     * @var TaskStatus
     */
    private $taskStatus;
    /**
     * @var TaskStatusHelper
     */
    private $taskStatusHelper;

    protected function setUp()
    {
        $this->taskStatus = new TaskStatus('test-id');
        $this->taskStatusHelper = $this
            ->getMockBuilder(TaskStatusHelper::class)->disableOriginalConstructor()->getMock();
    }

    public function testExecute()
    {
        $controllerAction = function (TaskStatus $taskStatus) {
            return 'id:'.$taskStatus->getId();
        };
        $this->taskStatusHelper->expects($this->once())
            ->method('toProcessing')->id('to-processing');
        $this->taskStatusHelper->expects($this->once())
            ->method('setResult')->with('id:'.$this->taskStatus->getId())->id('set-result')->after('to-processing');
        $this->taskStatusHelper->expects($this->once())
            ->method('toFinished')->after('set-result');
        $this->taskStatusHelper->expects($this->never())
            ->method('toFailed');

        $task = new Task($controllerAction, [$this->taskStatus], $this->taskStatusHelper);
        $task->execute();
    }

    public function testExecuteFail()
    {
        $controllerAction = function (TaskStatus $taskStatus) {
            throw new \Exception('Test exception: '.$taskStatus->getId());
        };
        $this->taskStatusHelper->expects($this->once())
            ->method('toProcessing')->id('to-processing');
        $this->taskStatusHelper->expects($this->never())
            ->method('setResult')->with('id:'.$this->taskStatus->getId())->id('set-result')->after('to-processing');
        $this->taskStatusHelper->expects($this->never())
            ->method('toFinished')->after('set-result');
        $this->taskStatusHelper->expects($this->once())
            ->method('toFailed');
        $this->expectException(AsyncTaskException::class);

        $task = new Task($controllerAction, [$this->taskStatus], $this->taskStatusHelper);
        $task->execute();
    }
}
