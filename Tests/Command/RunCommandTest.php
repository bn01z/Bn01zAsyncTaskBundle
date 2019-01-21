<?php

namespace Bn01z\AsyncTask\Tests\Command;

use Bn01z\AsyncTask\Command\RunCommand;
use Bn01z\AsyncTask\Process\ProcessManager;
use Bn01z\AsyncTask\Process\TaskProcessor;
use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Status\WebSocketNotificator;
use Bn01z\AsyncTask\Status\WebSocketServer;
use Bn01z\AsyncTask\Task\AsyncTaskException;
use Bn01z\AsyncTask\Tests\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends TestCase
{
    private $taskProcessor;
    private $processManager;
    private $commandTester;
    private $socket;

    protected function setUp()
    {
        $this->taskProcessor = $this->getMockBuilder(TaskProcessor::class)->getMock();
        $this->processManager = $this->getMockBuilder(ProcessManager::class)->getMock();

        $this->socket = new WebSocketServer(
            $address = $_ENV['WEB_SOCKET_ADDRESS'] ?? '0.0.0.0:8080',
            $this->getMockBuilder(WebSocketNotificator::class)->getMock(),
            $this->getMockBuilder(TaskStatusManager::class)->getMock()
        );

        $application = new Application();
        $application->add(new RunCommand($this->taskProcessor, $this->processManager, $this->socket));
        $command = $application->find('bn01z:async-call:run');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecute()
    {
        $this->processManager->expects($this->once())
            ->method('run')->with([$this->socket, 'run']);
        $this->taskProcessor->expects($this->once())
            ->method('process')->with(4);

        $this->commandTester->execute(['-w' => 4], ['verbosity' => Output::VERBOSITY_VERBOSE]);
    }

    public function testExecuteWebSocketShouldFail()
    {
        $this->processManager->expects($this->once())
            ->method('run')->with([$this->socket, 'run'])->willThrowException(new AsyncTaskException('Test socket error'));
        $this->taskProcessor->expects($this->once())
            ->method('process')->with(4);

        $this->commandTester->execute(['-w' => 4], ['verbosity' => Output::VERBOSITY_VERBOSE]);

        $this->assertContains('[ERROR] Status server: Test socket error', $this->commandTester->getDisplay());
    }

    public function testExecuteProcessorShouldFail()
    {
        $this->processManager->expects($this->once())
            ->method('run')->with([$this->socket, 'run']);
        $this->taskProcessor->expects($this->once())
            ->method('process')->with(4)->willThrowException(new AsyncTaskException('Test process error'));

        $this->commandTester->execute(['-w' => 4], ['verbosity' => Output::VERBOSITY_VERBOSE]);

        $this->assertContains('[ERROR] Queue processor: Test process error', $this->commandTester->getDisplay());
    }
}
