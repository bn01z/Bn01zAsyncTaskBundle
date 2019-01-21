<?php

namespace Bn01z\AsyncTask\Tests\Queue\Adapter;

use Bn01z\AsyncTask\Queue\Adapter\ZMQTaskQueue;
use Bn01z\AsyncTask\Queue\TaskQueueException;
use Bn01z\AsyncTask\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ZMQTaskQueueTest extends TestCase
{
    private $tmpDirPath;
    private $frontendConnection;
    private $backendConnection;

    protected function setUp()
    {
        $this->tmpDirPath = $_ENV['TMP_DIR_PATH'] ?? './tmp';
        mkdir($this->tmpDirPath, 0777, true);
        $this->tmpDirPath = realpath($this->tmpDirPath);
        $this->frontendConnection = sprintf('ipc://%s/frontend.ipc', $this->tmpDirPath);
        $this->backendConnection = sprintf('ipc://%s/backend.ipc', $this->tmpDirPath);
    }

    protected function tearDown()
    {
        $frontConnectionPath = sprintf('%s/frontend.ipc', $this->tmpDirPath);
        $backConnectionPath = sprintf('%s/backend.ipc', $this->tmpDirPath);

        if (file_exists($this->tmpDirPath)) {
            chmod($this->tmpDirPath, 0755);
            if (file_exists($frontConnectionPath)) {
                unlink($frontConnectionPath);
            }
            if (file_exists($backConnectionPath)) {
                unlink($backConnectionPath);
            }
            rmdir($this->tmpDirPath);
        }
    }

    public function testAdd()
    {
        try {
            $socket = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REP);
            $socket->bind($this->frontendConnection);
            $request = $this->createSymfonyRequest();
            $queue = new ZMQTaskQueue($this->frontendConnection, $this->backendConnection);

            $queue->add($request);

            $this->assertEquals(serialize($request), $socket->recv());

            $socket->unbind($this->frontendConnection);
        } catch (\ZMQSocketException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testAddWithError()
    {
        $request = $this->getMockBuilder(Request::class)->getMock();
        $request->expects($this->once())
            ->method('hasSession')->willThrowException(new \Exception('test exception'));
        $queue = new ZMQTaskQueue($this->frontendConnection, $this->backendConnection);

        $this->expectException(TaskQueueException::class);

        $queue->add($request);
    }

    public function testGetNext()
    {
        try {
            $socket = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REQ);
            $socket->bind($this->backendConnection, true);
            $queue = new ZMQTaskQueue($this->frontendConnection, $this->backendConnection);
            $queue->connectToReceiverSocket();

            $request = $this->createSymfonyRequest();
            $socket->send(serialize($request));

            $this->assertEquals($request, $queue->getNext());

            $socket->unbind($this->backendConnection);
        } catch (\ZMQSocketException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testGetNextWithError()
    {
        try {
            $socket = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REQ);
            $socket->bind($this->backendConnection, true);
            $queue = new ZMQTaskQueue($this->frontendConnection, $this->backendConnection);
            $queue->connectToReceiverSocket();

            $socket->send('not properly serialized request');

            $this->expectException(TaskQueueException::class);

            $queue->getNext();

            $socket->unbind($this->backendConnection);
        } catch (\ZMQSocketException $e) {
            $this->fail($e->getMessage());
        }
    }
}