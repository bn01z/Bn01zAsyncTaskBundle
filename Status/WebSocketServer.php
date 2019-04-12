<?php

namespace Bn01z\AsyncTask\Status;

use Bn01z\AsyncTask\Task\TaskStatus;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServer;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Server;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class WebSocketServer implements WampServerInterface
{
    /**
     * @var array
     */
    private $topics = [];
    /**
     * @var string
     */
    private $webSocketAddress;
    /**
     * @var WebSocketNotificator
     */
    private $notificator;
    /**
     * @var TaskStatusManager
     */
    private $status;

    public function __construct(string $webSocketAddress, WebSocketNotificator $notificator, TaskStatusManager $status)
    {
        $this->webSocketAddress = $webSocketAddress;
        $this->notificator = $notificator;
        $this->status = $status;
    }

    public function run(LoopInterface $loop = null): void
    {
        // @codeCoverageIgnoreStart
        if (!$loop instanceof LoopInterface) {
            $loop = Factory::create();
        }
        // @codeCoverageIgnoreEnd

        $this->notificator->listen([$this, 'onStatusChange'], $loop);

        $webSock = new Server($this->webSocketAddress, $loop);
        $server = new IoServer(new HttpServer(new WsServer(new WampServer($this))), $webSock, $loop);
        $server->run();
    }

    public function onStatusChange(string $data)
    {
        /** @var TaskStatus $status */
        $status = unserialize($data);
        $changedTopicName = $status->getChangedStatusTopicName();
        if (isset($this->topics[$changedTopicName])) {
            $this->topics[$changedTopicName]->broadcast($status);
        }
        $finishedTopicName = $status->getFinishedStatusTopicName();
        if (TaskStatus::STATUS_FINISHED === $status->getStatus() && isset($this->topics[$finishedTopicName])) {
            $this->topics[$finishedTopicName]
                 ->broadcast($this->extractResult($status->getResult()));
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $conn->send(json_encode('Connected!'));
    }

    public function onClose(ConnectionInterface $conn)
    {
        foreach ($this->topics as $topic) {
            $this->onUnSubscribe($conn, $topic);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->send(json_encode(sprintf('Error: %s', $e->getMessage())));
    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        $conn->close(); // This is not allowed
    }

    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        $topicName = $topic->getId();
        $this->addTopic($topic);
        $identifier = TaskStatus::getIdentifierFromTopicName($topicName);
        if (!$identifier) {
            return;
        }
        $status = $this->status->get($identifier);
        if (TaskStatus::isChangedStatusTopicName($topicName)) {
            $topic->broadcast($status);
        }
        if (TaskStatus::isFinishedStatusTopicName($topicName)
            && TaskStatus::STATUS_FINISHED === $status->getStatus()) {
            $topic->broadcast($this->extractResult($status->getResult()));
        }
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        if ($topic->has($conn)) {
            $topic->remove($conn);
        }
        if (0 === $topic->count()) {
            $this->removeTopic($topic);
        }
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        $conn->close(); // This is not allowed
    }

    public function addTopic(Topic $topic)
    {
        $this->topics[$topic->getId()] = $topic;
    }

    public function removeTopic(Topic $topic)
    {
        unset($this->topics[$topic->getId()]);
    }

    private function extractResult($result)
    {
        if ($result instanceof JsonResponse) {
            return json_decode($result->getContent(), true);
        }
        if ($result instanceof Response) {
            return $result->getContent();
        }

        return $result;
    }
}
