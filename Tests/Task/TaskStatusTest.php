<?php

namespace Bn01z\AsyncTask\Tests\Task;

use Bn01z\AsyncTask\Task\AsyncTaskException;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Tests\TestCase;

class TaskStatusTest extends TestCase
{
    /**
     * @var TaskStatus
     */
    private $taskStatus;

    protected function setUp()
    {
        $this->taskStatus = new TaskStatus('test-id');
    }

    public function testGetIdentifierFromTopicName()
    {
        $this->assertEquals(
            $this->taskStatus->getId(),
            TaskStatus::getIdentifierFromTopicName($this->taskStatus->getFinishedStatusTopicName())
        );
        $this->assertEquals(
            $this->taskStatus->getId(),
            TaskStatus::getIdentifierFromTopicName($this->taskStatus->getChangedStatusTopicName())
        );
    }

    public function testIsFinishedStatusTopicName()
    {
        $this->assertTrue(TaskStatus::isFinishedStatusTopicName($this->taskStatus->getFinishedStatusTopicName()));
        $this->assertFalse(TaskStatus::isFinishedStatusTopicName('not-finished/'.$this->taskStatus->getId()));
    }

    public function testIsChangedStatusTopicName()
    {
        $this->assertTrue(TaskStatus::isChangedStatusTopicName($this->taskStatus->getChangedStatusTopicName()));
        $this->assertFalse(TaskStatus::isChangedStatusTopicName('not-changed/'.$this->taskStatus->getId()));
    }

    public function testGetId()
    {
        $this->assertEquals('test-id', $this->taskStatus->getId());
    }

    public function testGetSetProgress()
    {
        $this->assertEquals(0, $this->taskStatus->getProgress());
        $this->taskStatus->setProgress(10.20);
        $this->assertEquals(10.20, $this->taskStatus->getProgress());
    }

    public function testGetSetMessage()
    {
        $message = 'test message';
        $this->taskStatus->setMessage($message);
        $this->assertEquals($message, $this->taskStatus->getMessage());
    }

    public function testGetSetResult()
    {
        $result = 'test result';
        $this->taskStatus->setResult($result);
        $this->assertEquals($result, $this->taskStatus->getResult());
    }

    public function testGetSetStatus()
    {
        $this->assertEquals(TaskStatus::STATUS_QUEUED, $this->taskStatus->getStatus());
        $this->taskStatus->setStatus(TaskStatus::STATUS_PROCESSING);
        $this->assertEquals(TaskStatus::STATUS_PROCESSING, $this->taskStatus->getStatus());
    }

    public function testSetStatusError()
    {
        $this->expectException(AsyncTaskException::class);
        $this->taskStatus->setStatus(123);
    }

    public function testGetChangedStatusTopicName()
    {
        self::assertEquals('changed/test-id', $this->taskStatus->getChangedStatusTopicName());
    }

    public function testGetFinishedStatusTopicName()
    {
        self::assertEquals('finished/test-id', $this->taskStatus->getFinishedStatusTopicName());
    }

    public function testJsonSerialize()
    {
        $serialized = '{"id":"test-id","message":"test message","progress":50,"status":1,"topics":{"changed":"changed\/test-id","finished":"finished\/test-id"}}';

        $this->taskStatus->setStatus(TaskStatus::STATUS_QUEUED);
        $this->taskStatus->setResult('test result');
        $this->taskStatus->setMessage('test message');
        $this->taskStatus->setProgress(50);

        self::assertEquals($serialized, json_encode($this->taskStatus));
    }
}
