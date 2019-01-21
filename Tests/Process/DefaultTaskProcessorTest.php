<?php

namespace Bn01z\AsyncTask\Tests\Process;

use Bn01z\AsyncTask\Process\ProcessManager;
use Bn01z\AsyncTask\Process\DefaultTaskProcessor;
use Bn01z\AsyncTask\Queue\Runnable;
use Bn01z\AsyncTask\Queue\TaskQueue;
use Bn01z\AsyncTask\Task\AsyncTaskException;
use Bn01z\AsyncTask\Task\Task;
use Bn01z\AsyncTask\Task\TaskFactory;
use Bn01z\AsyncTask\Task\TaskStatusHelper;
use Bn01z\AsyncTask\Tests\TestCase;

class DefaultTaskProcessorTest extends TestCase
{
    private $processor;
    private $queue;
    private $taskFactory;
    private $processManager;

    protected function setUp()
    {
        $this->queue = $this->getMockBuilder([TaskQueue::class, Runnable::class])->getMock();
        $this->taskFactory = $this->getMockBuilder(TaskFactory::class)->getMock();
        $this->processManager = $this->getMockBuilder(ProcessManager::class)->getMock();
        $this->processor = new DefaultTaskProcessor($this->queue, $this->taskFactory, $this->processManager);
    }

    public function testProcessWithoutParameters()
    {
        $this->processManager->expects($this->exactly(2))
            ->method('run')->withConsecutive(
                [[$this->processor, 'runWorker']],
                [[$this->queue, 'run']]
            );
        $this->processor->process();
    }

    public function testProcessWithParameters()
    {
        $this->processManager->expects($this->exactly(4))
            ->method('run')->withConsecutive(
                [[$this->processor, 'runWorker']],
                [[$this->processor, 'runWorker']],
                [[$this->processor, 'runWorker']],
                [[$this->queue, 'run']]
            );
        $this->processor->process(3);
    }

    public function testProcessWithInvalidParameters()
    {
        $this->processManager->expects($this->never())
            ->method('run');
        $this->expectException(AsyncTaskException::class);
        $this->processor->process(-1);
    }

    public function testRunTask()
    {
        $helper = $this->getMockBuilder(TaskStatusHelper::class)->disableOriginalConstructor()->getMock();
        $request = $this->createSymfonyRequest();
        $task = new Task(
            function () {

            },
            [],
            $helper
        );

        $this->queue->expects($this->once())
            ->method('getNext')->willReturn($request);
        $this->taskFactory->expects($this->once())
            ->method('createFromRequest')->with($request)->willReturn($task);

        $this->assertTrue($this->processor->runTask());
    }

    public function testRunTaskWithError()
    {
        $helper = $this->getMockBuilder(TaskStatusHelper::class)->disableOriginalConstructor()->getMock();
        $request = $this->createSymfonyRequest();
        $task = new Task(
            function () {
                throw new \Exception('Test exception');
            },
            [],
            $helper
        );

        $this->queue->expects($this->once())
            ->method('getNext')->willReturn($request);
        $this->taskFactory->expects($this->once())
            ->method('createFromRequest')->with($request)->willReturn($task);

        $this->assertFalse($this->processor->runTask());
    }
}
