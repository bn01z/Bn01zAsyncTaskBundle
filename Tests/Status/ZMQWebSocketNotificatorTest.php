<?php

namespace Bn01z\AsyncTask\Tests\Status;

use Bn01z\AsyncTask\Status\TaskStatusException;
use Bn01z\AsyncTask\Status\ZMQWebSocketNotificator;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Tests\TestCase;
use React\EventLoop\Factory;

class ZMQWebSocketNotificatorTest extends TestCase
{
    private $taskStatus;
    private $connection;
    private $invalidConnection;
    private $tmpDirPath;

    protected function setUp()
    {
        $this->tmpDirPath = $_ENV['TMP_DIR_PATH'] ?? './tmp';
        mkdir($this->tmpDirPath, 0777, true);
        $this->tmpDirPath = realpath($this->tmpDirPath);

        $this->taskStatus = new TaskStatus('test-id');
        $this->connection = sprintf('ipc://%s/bn01z_temp.ipc', $this->tmpDirPath);
        $this->invalidConnection = sprintf('not-available://%s/bn01z_temp_error.ipc', $this->tmpDirPath);
    }

    protected function tearDown()
    {
        $file = sprintf('%s/bn01z_temp.ipc', $this->tmpDirPath);
        if (file_exists($this->tmpDirPath)) {
            if (file_exists($file)) {
                unlink($file);
            }
            rmdir($this->tmpDirPath);
        }
    }

    public function testNotify()
    {
        try {
            $socket = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_PULL);
            $socket->bind($this->connection);
            $notificator = new ZMQWebSocketNotificator($this->connection);
            $notificator->notify($this->taskStatus);

            $data = $socket->recv();
            $socket->unbind($this->connection);
            $this->assertEquals(serialize($this->taskStatus), $data);
        } catch (\ZMQSocketException $e) {
            $this->fail('ZMQ notify test failed');
        }
    }

    public function testNotifyError()
    {
        $this->expectException(TaskStatusException::class);

        $notificator = new ZMQWebSocketNotificator($this->invalidConnection);
        $notificator->notify($this->taskStatus);
    }

    public function testListen()
    {
        $callback = function ($data) {
            $this->assertEquals(serialize($this->taskStatus), $data);
        };
        $loop = Factory::create();
        $loop->addTimer(0.01, function () {
            $socket = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_PUSH);
            $socket->connect($this->connection);
            $socket->send(serialize($this->taskStatus));
        });
        $loop->addTimer(0.1, function () use ($loop) {
            $loop->stop();
        });

        $notificator = new ZMQWebSocketNotificator($this->connection);
        $notificator->listen($callback, $loop);

        $loop->run();
    }

    public function testListenError()
    {
        $this->expectException(TaskStatusException::class);

        $callback = function ($data) {

        };
        $loop = Factory::create();

        $notificator = new ZMQWebSocketNotificator($this->invalidConnection);
        $notificator->listen($callback, $loop);
    }
}