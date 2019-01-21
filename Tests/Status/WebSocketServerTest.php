<?php

namespace Bn01z\AsyncTask\Tests\Status;

use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Status\WebSocketNotificator;
use Bn01z\AsyncTask\Status\WebSocketServer;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Tests\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use React\EventLoop\LoopInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WebSocketServerTest extends TestCase
{
    private $notificator;
    private $statusManager;
    private $server;
    private $connection;

    protected function setUp()
    {
        $address = $_ENV['WEB_SOCKET_ADDRESS'] ?? '0.0.0.0:8080';
        $this->notificator = $this->getMockBuilder(WebSocketNotificator::class)->getMock();
        $this->statusManager = $this->getMockBuilder(TaskStatusManager::class)->getMock();
        $this->connection = $this->getMockBuilder(ConnectionInterface::class)->getMock();
        $this->server = new WebSocketServer($address, $this->notificator, $this->statusManager);
    }

    public function testSubscribeToChanged()
    {
        $status = new TaskStatus('test-id');

        $topic = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();
        $topic->expects($this->exactly(2))
            ->method('getId')->willReturn($status->getChangedStatusTopicName());
        $this->statusManager->expects($this->once())
            ->method('get')->with($status->getId())->willReturn($status);
        $topic->expects($this->once())
            ->method('broadcast')->with($status);

        $this->server->onSubscribe($this->connection, $topic);
    }

    public function testSubscribeToFinishedJsonResponse()
    {
        $data = ['response'];
        $status = new TaskStatus('test-id');
        $status->setStatus(TaskStatus::STATUS_FINISHED);
        $status->setResult(JsonResponse::create($data));

        $topic = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();
        $topic->expects($this->exactly(2))
            ->method('getId')->willReturn($status->getFinishedStatusTopicName());
        $this->statusManager->expects($this->once())
            ->method('get')->with($status->getId())->willReturn($status);
        $topic->expects($this->once())
            ->method('broadcast')->with($data);

        $this->server->onSubscribe($this->connection, $topic);
    }

    public function testSubscribeToFinishedResponse()
    {
        $data = 'regular response';
        $status = new TaskStatus('test-id');
        $status->setStatus(TaskStatus::STATUS_FINISHED);
        $status->setResult(Response::create($data));

        $topic = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();
        $topic->expects($this->exactly(2))
            ->method('getId')->willReturn($status->getFinishedStatusTopicName());
        $this->statusManager->expects($this->once())
            ->method('get')->with($status->getId())->willReturn($status);
        $topic->expects($this->once())
            ->method('broadcast')->with($data);

        $this->server->onSubscribe($this->connection, $topic);
    }


    public function testSubscribeToFinishedRest()
    {
        $data = ['response'];
        $status = new TaskStatus('test-id');
        $status->setStatus(TaskStatus::STATUS_FINISHED);
        $status->setResult($data);
        $topic = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();

        $topic->expects($this->exactly(2))
            ->method('getId')->willReturn($status->getFinishedStatusTopicName());
        $this->statusManager->expects($this->once())
            ->method('get')->with($status->getId())->willReturn($status);
        $topic->expects($this->once())
            ->method('broadcast')->with($data);

        $this->server->onSubscribe($this->connection, $topic);
    }

    public function testSubscribeToWrongTopic()
    {
        $topic = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();

        $topic->expects($this->exactly(2))
            ->method('getId')->willReturn('wrong-topic');
        $this->statusManager->expects($this->never())->method('get');
        $topic->expects($this->never())->method('broadcast');

        $this->server->onSubscribe($this->connection, $topic);
    }

    public function testSubscribeToFinishedButNotYet()
    {
        $status = new TaskStatus('test-id');
        $topic = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();

        $topic->expects($this->exactly(2))
            ->method('getId')->willReturn($status->getFinishedStatusTopicName());
        $this->statusManager->expects($this->once())
            ->method('get')->with($status->getId())->willReturn($status);
        $topic->expects($this->never())->method('broadcast');

        $this->server->onSubscribe($this->connection, $topic);
    }

    public function testOnUnsubscribeWithExistingTopic()
    {
        $status = new TaskStatus('test-id');
        $topic = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();

        $topic->expects($this->once())
            ->method('getId')->willReturn($status->getFinishedStatusTopicName());
        $topic->expects($this->once())
            ->method('has')->willReturn(true);
        $topic->expects($this->once())
            ->method('remove');
        $topic->expects($this->once())
            ->method('count')->willReturn(0);

        $this->server->onUnSubscribe($this->connection, $topic);
    }

    public function testOnPublish()
    {
        $this->connection->expects($this->once())
            ->method('close');

        $this->server->onPublish($this->connection, '', '', [], []);
    }

    public function testOnCall()
    {
        $this->connection->expects($this->once())
            ->method('close');

        $this->server->onCall($this->connection, '', '', []);
    }

    public function testRun()
    {
        $loop = $this->getMockBuilder(LoopInterface::class)->getMock();

        $loop->expects($this->once())
            ->method('run');
        $this->notificator->expects($this->once())
            ->method('listen')->with([$this->server, 'onStatusChange'], $loop);

        $this->server->run($loop);
    }

    public function testOnStatusChangeNotExisting()
    {
        $data = ['result'];
        $status = new TaskStatus('test-id');
        $status->setStatus(TaskStatus::STATUS_FINISHED);
        $status->setResult($data);
        $topicChanged = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();
        $topicFinished = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();
        $anotherTopic = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();

        $topicChanged->expects($this->once())
            ->method('getId')->willReturn($status->getChangedStatusTopicName());
        $topicChanged->expects($this->once())
            ->method('broadcast')->with($status);
        $topicFinished->expects($this->once())
            ->method('getId')->willReturn($status->getFinishedStatusTopicName());
        $topicFinished->expects($this->once())
            ->method('broadcast')->with($data);
        $anotherTopic->expects($this->once())
            ->method('getId')->willReturn('another-topic');
        $anotherTopic->expects($this->never())
            ->method('broadcast');

        $this->server->addTopic($topicChanged);
        $this->server->addTopic($topicFinished);
        $this->server->addTopic($anotherTopic);

        $this->server->onStatusChange(serialize($status));

    }

    public function testOnOpen()
    {
        $this->connection->expects($this->once())
            ->method('send')->with('Connected!');

        $this->server->onOpen($this->connection);
    }

    public function testOnError()
    {
        $exception = new \Exception('Test error!');
        $this->connection->expects($this->once())
            ->method('send')->with(sprintf('Error: %s', $exception->getMessage()));

        $this->server->onError($this->connection, $exception);
    }

    public function testOnClose()
    {
        $status = new TaskStatus('test-id');
        $topic = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();
        $topic2 = $this->getMockBuilder(Topic::class)->disableOriginalConstructor()->getMock();

        $topic->expects($this->exactly(2))
            ->method('getId')->willReturn($status->getFinishedStatusTopicName());
        $topic->expects($this->once())
            ->method('has')->willReturn(true);
        $topic->expects($this->once())
            ->method('remove');
        $topic->expects($this->once())
            ->method('count')->willReturn(0);

        $topic2->expects($this->exactly(2))
            ->method('getId')->willReturn($status->getChangedStatusTopicName());
        $topic2->expects($this->once())
            ->method('has')->willReturn(true);
        $topic2->expects($this->once())
            ->method('remove');
        $topic2->expects($this->once())
            ->method('count')->willReturn(0);

        $this->server->addTopic($topic);
        $this->server->addTopic($topic2);

        $this->server->onClose($this->connection);
    }
}