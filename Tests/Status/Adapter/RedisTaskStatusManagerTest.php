<?php

namespace Bn01z\AsyncTask\Tests\Status\Adapter;

use Bn01z\AsyncTask\Status\Adapter\RedisTaskStatusManager;
use Bn01z\AsyncTask\Status\TaskStatusException;
use Bn01z\AsyncTask\Status\WebSocketNotificator;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Tests\TestCase;
use Predis\Client as RedisClient;

class RedisTaskStatusManagerTest extends TestCase
{
    private $redis;
    private $taskStatus;
    private $notificator;
    private $statusManager;

    protected function setUp()
    {
        $connection = $_ENV['REDIS_CONNECTION'] ?? 'tcp://127.0.0.1:6379';
        $this->redis = new RedisClient($connection, ['prefix' => 'bn01z_async_task_status:']);
        $this->taskStatus = new TaskStatus('test-id');
        $this->notificator = $this->getMockBuilder(WebSocketNotificator::class)->getMock();
        $this->statusManager = new RedisTaskStatusManager($connection, $this->notificator);
    }

    protected function tearDown()
    {
        $this->redis->del([$this->taskStatus->getId()]);
    }

    public function testSet()
    {
        $this->notificator->expects($this->once())->method('notify')->with($this->taskStatus);

        $this->statusManager->set($this->taskStatus);

        $this->assertEquals(
            serialize($this->taskStatus),
            $this->redis->get($this->taskStatus->getId())
        );
    }

    public function testGet()
    {
        $this->redis->set($this->taskStatus->getId(), serialize($this->taskStatus));

        $this->notificator->expects($this->never())->method('notify')->with($this->taskStatus);

        $status = $this->statusManager->get('test-id');

        $this->assertEquals(
            serialize($this->taskStatus),
            serialize($status)
        );
    }

    public function testGetErrorMissingStatus()
    {
        $this->notificator->expects($this->never())->method('notify')->with($this->taskStatus);
        $this->expectException(TaskStatusException::class);

        $this->statusManager->get('test-id');
    }

    public function testGetErrorCorruptedData()
    {
        $this->redis->set($this->taskStatus->getId(), 'invalid data');

        $this->notificator->expects($this->never())->method('notify')->with($this->taskStatus);
        $this->expectException(TaskStatusException::class);

        $this->statusManager->get('test-id');
    }

    public function testCreate()
    {
        $this->taskStatus->setMessage('queued');
        $this->notificator->expects($this->once())->method('notify');

        $status = $this->statusManager->create('test-id');

        $this->assertEquals(
            serialize($this->taskStatus),
            serialize($status)
        );
        $this->assertEquals(
            serialize($this->taskStatus),
            $this->redis->get($this->taskStatus->getId())
        );
    }
}