<?php

namespace Bn01z\AsyncTask\Tests\Queue\Adapter;

use Bn01z\AsyncTask\Queue\Adapter\RedisTaskQueue;
use Bn01z\AsyncTask\Queue\TaskQueueException;
use Bn01z\AsyncTask\Tests\TestCase;
use Predis\Client as RedisClient;
use Symfony\Component\HttpFoundation\Request;

class RedisTaskQueueTest extends TestCase
{
    private $redis;
    private $queueName;
    private $queue;

    protected function setUp()
    {
        $connection = $_ENV['REDIS_CONNECTION'] ?? 'tcp://127.0.0.1:6379';
        $this->queueName = 'queue';
        $this->redis = new RedisClient($connection, ['prefix' => 'bn01z_async_task:']);
        $this->queue = new RedisTaskQueue($connection, $this->queueName);
    }

    protected function tearDown()
    {
        $this->redis->del([$this->queueName]);
    }

    public function testAdd()
    {
        $request = $this->createSymfonyRequest();

        $this->queue->add($request);

        $this->assertEquals(serialize($request), $this->redis->lpop($this->queueName));
    }

    public function testAddWithError()
    {
        $request = $this->getMockBuilder(Request::class)->getMock();
        $request->expects($this->once())
            ->method('hasSession')->willThrowException(new \Exception('test exception'));

        $this->expectException(TaskQueueException::class);

        $this->queue->add($request);
    }

    public function testGetNext()
    {
        $request = $this->createSymfonyRequest();
        $this->redis->rpush($this->queueName, [serialize($request)]);

        self::assertEquals($request, $this->queue->getNext());
    }

    public function testGetNextWithError()
    {
        $this->redis->rpush($this->queueName, ['not properly serialized request']);

        $this->expectException(TaskQueueException::class);

        $this->queue->getNext();
    }
}