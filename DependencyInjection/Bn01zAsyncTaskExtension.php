<?php

namespace Bn01z\AsyncTask\DependencyInjection;

use Bn01z\AsyncTask\Queue\Adapter\RedisTaskQueue;
use Bn01z\AsyncTask\Queue\Adapter\ZMQTaskQueue;
use Bn01z\AsyncTask\Queue\TaskQueue;
use Bn01z\AsyncTask\Status\Adapter\FileTaskStatusManager;
use Bn01z\AsyncTask\Status\Adapter\RedisTaskStatusManager;
use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Status\RequestStatusAttributeManager;
use Bn01z\AsyncTask\Status\WebSocketNotificator;
use Bn01z\AsyncTask\Status\ZMQWebSocketNotificator;
use Bn01z\AsyncTask\Status\WebSocketServer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class Bn01zAsyncTaskExtension extends Extension
{
    const QUEUES = [
        'redis' => RedisTaskQueue::class,
        'zmq' => ZMQTaskQueue::class,
    ];

    const STATUSES = [
        'file' => FileTaskStatusManager::class,
        'redis' => RedisTaskStatusManager::class,
    ];

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $locator = new FileLocator(__DIR__.'/../Resources/config');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load('services.yaml');

        $this->configureWebSocketService($container, $config['web_socket']);
        $this->configureQueueService($container, $config['queue']);
        $this->configureStatusService($container, $config['status']);
        $this->configureStatusAttributeManagerService($container, $config['attribute_name']);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameterBag());
    }

    private function configureQueueService(ContainerBuilder $container, $config): void
    {
        $serviceName = $config['use'];
        if (isset(self::QUEUES[$serviceName])) {
            $adapterConfig = $config['adapters'][$serviceName];
            $queueService = $container->getDefinition(TaskQueue::class);
            $queueService->setClass(self::QUEUES[$serviceName]);
            switch ($serviceName) {
                case 'redis':
                    $queueService->setBindings([
                        '$connection' => $adapterConfig['connection'],
                        '$queueName' => $adapterConfig['queue_name'],
                    ]);
                    break;
                case 'zmq':
                    $queueService->setBindings([
                        '$frontendConnection' => $adapterConfig['frontend_connection'],
                        '$backendConnection' => $adapterConfig['backend_connection'],
                    ]);
                    break;
            }
        }
    }

    private function configureStatusService(ContainerBuilder $container, $config): void
    {
        $serviceName = $config['use'];
        if (isset(self::STATUSES[$serviceName])) {
            $adapterConfig = $config['adapters'][$serviceName];
            $statusService = $container->getDefinition(TaskStatusManager::class);
            $statusService->setClass(self::STATUSES[$serviceName]);
            switch ($serviceName) {
                case 'redis':
                    $statusService->setBindings([
                        '$connection' => $adapterConfig['connection'],
                    ]);
                    break;
                case 'file':
                    $statusService->setBindings([
                        '$saveDir' => $adapterConfig['save_dir'],
                    ]);
                    break;
            }
        }
    }

    private function configureStatusAttributeManagerService(ContainerBuilder $container, string $statusAttributeName)
    {
        $container->getDefinition(RequestStatusAttributeManager::class)->setBindings([
            '$statusAttributeName' => $statusAttributeName,
        ]);
    }

    private function configureWebSocketService(ContainerBuilder $container, array $config)
    {
        if (!$config['enabled']) {
            $container->removeDefinition(WebSocketServer::class);
            $container->removeDefinition(WebSocketNotificator::class);
            return;
        }
        $container
            ->getDefinition(WebSocketServer::class)
            ->setBindings([
                '$webSocketAddress' => $config['address'],
            ]);
        $notificator = $container->getDefinition(WebSocketNotificator::class);
        if ($notificator->getClass() === ZMQWebSocketNotificator::class) {
            $notificator->setBindings([
                '$connection' => $config['notificator_connection'],
            ]);
        }
    }
}
