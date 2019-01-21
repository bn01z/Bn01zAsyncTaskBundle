<?php

namespace Bn01z\AsyncTask\Queue\Adapter;

use Bn01z\AsyncTask\Queue\Runnable;
use Bn01z\AsyncTask\Queue\TaskQueue;
use Bn01z\AsyncTask\Queue\TaskQueueException;
use Symfony\Component\HttpFoundation\Request;
use ZMQ;
use ZMQContext;
use ZMQDevice;
use ZMQSocket;

final class ZMQTaskQueue implements TaskQueue, Runnable
{
    /**
     * @var string
     */
    private $frontendConnection;
    /**
     * @var string
     */
    private $backendConnection;
    /**
     * @var ZMQSocket
     */
    private $sender;
    /**
     * @var ZMQSocket
     */
    private $receiver;

    public function __construct(string $frontendConnection, string $backendConnection)
    {
        $this->frontendConnection = $frontendConnection;
        $this->backendConnection = $backendConnection;
    }

    public function add(Request $request): void
    {
        try {
            $this->connectToSenderSocket();
            if ($request->hasSession()) { // Fix to "unwrap" session closure which prevents serialization
                $request->getSession();
            }
            $this->sender->send(serialize($request));
        } catch (\Throwable $exception) {
            throw new TaskQueueException('Error sending message to socket', $exception);
        }
    }

    public function getNext(): Request
    {
        try {
            $this->connectToReceiverSocket();
            $request = unserialize($this->receiver->recv());
            $this->receiver->send('OK!');

            return $request;
        } catch (\Throwable $exception) {
            throw new TaskQueueException('Error running ZMQ worker', $exception);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function run(): void
    {
        try {
            $context = new ZMQContext();
            $clients = new ZMQSocket($context, ZMQ::SOCKET_ROUTER);
            $clients->bind($this->frontendConnection);
            $workers = new ZMQSocket($context, ZMQ::SOCKET_DEALER);
            $workers->bind($this->backendConnection);
            $device = new ZMQDevice($clients, $workers);
            $device->run();
        } catch (\ZMQSocketException | \ZMQDeviceException $exception) {
            throw new TaskQueueException('Error running ZMQ queue', $exception);
        }
    }

    /**
     * @throws \ZMQSocketException
     */
    public function connectToSenderSocket(): void
    {
        if (!$this->sender instanceof ZMQSocket) {
            $this->sender = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_REQ);
            $this->sender->connect($this->frontendConnection);
        }
    }

    /**
     * @throws \ZMQSocketException
     */
    public function connectToReceiverSocket(): void
    {
        if (!$this->receiver instanceof ZMQSocket) {
            $this->receiver = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_REP);
            $this->receiver->connect($this->backendConnection);
        }
    }
}
