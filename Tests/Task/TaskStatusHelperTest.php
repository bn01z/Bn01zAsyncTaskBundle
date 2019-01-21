<?php

namespace Bn01z\AsyncTask\Tests\Task;

use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Task\TaskStatusHelper;
use Bn01z\AsyncTask\Tests\TestCase;

class TaskStatusHelperTest extends TestCase
{
    /**
     * @var TaskStatus
     */
    private $taskStatus;
    /**
     * @var TaskStatusManager
     */
    private $taskStatusManager;
    /**
     * @var TaskStatusHelper
     */
    private $taskStatusHelper;

    protected function setUp()
    {
        $this->taskStatus = new TaskStatus('test-id');
        $this->taskStatusManager = $this->getMockBuilder(TaskStatusManager::class)->getMock();
        $this->taskStatusHelper = new TaskStatusHelper($this->taskStatus, $this->taskStatusManager);
    }

    public function testGetSetStatus()
    {
        $result = 'test result';

        $this->assertEquals($this->taskStatus, $this->taskStatusHelper->getStatus());
        $this->taskStatusHelper->setResult($result);
        $this->assertEquals($result, $this->taskStatus->getResult());
    }

    public function testToProcessing()
    {
        $message = 'test processing';
        $this->taskStatusManager->expects($this->once())->method('set')->with($this->taskStatus);

        $this->taskStatusHelper->toProcessing($message);
        $this->assertEquals(TaskStatus::STATUS_PROCESSING, $this->taskStatus->getStatus());
        $this->assertEquals($message, $this->taskStatus->getMessage());
    }

    public function testToFinished()
    {
        $message = 'test finished';
        $this->taskStatusManager->expects($this->once())->method('set')->with($this->taskStatus);

        $this->taskStatusHelper->toFinished($message);
        $this->assertEquals(TaskStatus::STATUS_FINISHED, $this->taskStatus->getStatus());
        $this->assertEquals($message, $this->taskStatus->getMessage());
    }

    public function testToFailed()
    {
        $message = 'test failed';
        $this->taskStatusManager->expects($this->once())->method('set')->with($this->taskStatus);

        $this->taskStatusHelper->toFailed($message);
        $this->assertEquals(TaskStatus::STATUS_FAILED, $this->taskStatus->getStatus());
        $this->assertEquals($message, $this->taskStatus->getMessage());
    }
}
