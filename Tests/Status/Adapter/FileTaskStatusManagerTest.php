<?php

namespace Bn01z\AsyncTask\Tests\Status\Adapter;

use Bn01z\AsyncTask\Status\Adapter\FileTaskStatusManager;
use Bn01z\AsyncTask\Status\TaskStatusException;
use Bn01z\AsyncTask\Status\WebSocketNotificator;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Tests\TestCase;

class FileTaskStatusManagerTest extends TestCase
{
    private $tmpDirPath;
    private $taskStatus;
    private $filePath;
    private $notificator;
    private $statusManager;

    protected function setUp()
    {
        $this->tmpDirPath = $_ENV['TMP_DIR_PATH'] ?? './tmp';
        $this->taskStatus = new TaskStatus('test-id');
        $this->filePath = sprintf('%s/%s', $this->tmpDirPath, $this->taskStatus->getId());
        $this->notificator = $this->getMockBuilder(WebSocketNotificator::class)->getMock();
        $this->statusManager = new FileTaskStatusManager($this->tmpDirPath, $this->notificator);
    }

    protected function tearDown()
    {
        if (file_exists($this->tmpDirPath)) {
            chmod($this->tmpDirPath, 0755);
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
            rmdir($this->tmpDirPath);
        }
    }

    public function testSet()
    {
        $this->notificator->expects($this->once())->method('notify')->with($this->taskStatus);

        $this->statusManager->set($this->taskStatus);

        $this->assertFileExists($this->filePath);
        $this->assertEquals(
            serialize($this->taskStatus),
            file_get_contents($this->filePath)
        );
    }

    public function testSetError()
    {
        chmod($this->tmpDirPath, 0555);

        $this->notificator->expects($this->never())->method('notify')->with($this->taskStatus);
        $this->expectException(TaskStatusException::class);

        $this->statusManager->set($this->taskStatus);
    }

    public function testGet()
    {
        file_put_contents($this->filePath, serialize($this->taskStatus));

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
        file_put_contents($this->filePath, 'invalid data');

        $this->notificator->expects($this->never())->method('notify')->with($this->taskStatus);
        $this->expectException(TaskStatusException::class);

        $this->statusManager->get('test-id');
    }

    public function testCreate()
    {
        $this->taskStatus->setMessage('queued');
        $this->notificator->expects($this->once())->method('notify');

        $status = $this->statusManager->create('test-id');

        $this->assertFileExists($this->filePath);
        $this->assertEquals(
            serialize($this->taskStatus),
            serialize($status)
        );
        $this->assertEquals(
            serialize($this->taskStatus),
            file_get_contents($this->filePath)
        );
    }
}