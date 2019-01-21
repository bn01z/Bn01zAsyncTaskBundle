<?php

namespace Bn01z\AsyncTask\Tests\DependencyInjection;

use Bn01z\AsyncTask\Command\RunCommand;
use Bn01z\AsyncTask\Controller\StatusController;
use Bn01z\AsyncTask\DependencyInjection\Bn01zAsyncTaskExtension;
use Bn01z\AsyncTask\EventListener\AsyncTaskListener;
use Bn01z\AsyncTask\Process\DefaultTaskProcessor;
use Bn01z\AsyncTask\Process\PcntlProcessManager;
use Bn01z\AsyncTask\Process\ProcessManager;
use Bn01z\AsyncTask\Process\TaskProcessor;
use Bn01z\AsyncTask\Queue\Adapter\RedisTaskQueue;
use Bn01z\AsyncTask\Queue\Adapter\ZMQTaskQueue;
use Bn01z\AsyncTask\Queue\TaskQueue;
use Bn01z\AsyncTask\Status\Adapter\FileTaskStatusManager;
use Bn01z\AsyncTask\Status\Adapter\RedisTaskStatusManager;
use Bn01z\AsyncTask\Status\RequestStatusAttributeManager;
use Bn01z\AsyncTask\Status\StatusIdGenerator;
use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Status\Uuid4StatusIdGenerator;
use Bn01z\AsyncTask\Status\WebSocketNotificator;
use Bn01z\AsyncTask\Status\WebSocketServer;
use Bn01z\AsyncTask\Status\ZMQWebSocketNotificator;
use Bn01z\AsyncTask\Task\DefaultTaskFactory;
use Bn01z\AsyncTask\Task\TaskFactory;
use Bn01z\AsyncTask\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Bn01zAsyncTaskExtensionTest extends TestCase
{
    private $containerBuilder;
    private $extension;

    protected function setUp()
    {
        $parameterBag = $this->getMockBuilder(ParameterBagInterface::class)->getMock();
        $parameterBag
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['kernel.project_dir'], ['kernel.cache_dir'])
            ->willReturnOnConsecutiveCalls('./tmp/project', './tmp/cache');
        $this->containerBuilder = new ContainerBuilder($parameterBag);
        $this->extension = new Bn01zAsyncTaskExtension();
    }

    public function testLoadDefault()
    {
        $this->extension->load([], $this->containerBuilder);

        $this->assertTrue($this->containerBuilder->hasDefinition(RequestStatusAttributeManager::class));
        $this->assertTrue($this->containerBuilder->hasDefinition(WebSocketServer::class));
        $this->assertTrue($this->containerBuilder->hasDefinition(RunCommand::class));
        $this->assertTrue($this->containerBuilder->hasDefinition(StatusController::class));
        $this->assertTrue($this->containerBuilder->hasDefinition(AsyncTaskListener::class));

        $this->assertEquals(PcntlProcessManager::class, $this->containerBuilder->getDefinition(ProcessManager::class)->getClass());
        $this->assertEquals(DefaultTaskProcessor::class, $this->containerBuilder->getDefinition(TaskProcessor::class)->getClass());
        $this->assertEquals(DefaultTaskFactory::class, $this->containerBuilder->getDefinition(TaskFactory::class)->getClass());
        $this->assertEquals(ZMQTaskQueue::class, $this->containerBuilder->getDefinition(TaskQueue::class)->getClass());
        $this->assertEquals(FileTaskStatusManager::class, $this->containerBuilder->getDefinition(TaskStatusManager::class)->getClass());
        $this->assertEquals(ZMQWebSocketNotificator::class, $this->containerBuilder->getDefinition(WebSocketNotificator::class)->getClass());
        $this->assertEquals(Uuid4StatusIdGenerator::class, $this->containerBuilder->getDefinition(StatusIdGenerator::class)->getClass());
    }

    public function testLoadRedisQueue()
    {
        $this->extension->load([['queue' => ['use' => 'redis']]], $this->containerBuilder);

        $this->assertEquals(RedisTaskQueue::class, $this->containerBuilder->getDefinition(TaskQueue::class)->getClass());
    }

    public function testLoadRedisTaskStatusManager()
    {
        $this->extension->load([['status' => ['use' => 'redis']]], $this->containerBuilder);

        $this->assertEquals(RedisTaskStatusManager::class, $this->containerBuilder->getDefinition(TaskStatusManager::class)->getClass());
    }

    public function testLoadDisableWebSocket()
    {
        $this->extension->load([['web_socket' => ['enabled' => false]]], $this->containerBuilder);

        $this->assertFalse($this->containerBuilder->hasDefinition(WebSocketServer::class));
        $this->assertFalse($this->containerBuilder->hasDefinition(WebSocketNotificator::class));
    }
}
