<?php

namespace Bn01z\AsyncTask\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;
    }

    public function getConfigTreeBuilder()
    {
        $rootName = 'bn01z_async_task';
        $newTreeBuilder = method_exists(TreeBuilder::class, 'getRootNode'); // Detecting new format specified in Symfony 4.2
        $treeBuilder = $newTreeBuilder ? new TreeBuilder($rootName) : new TreeBuilder();
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $newTreeBuilder ? $treeBuilder->getRootNode() : $treeBuilder->root($rootName);

        $rootChildren = $rootNode->children();
        $this->addQueueConfiguration($rootChildren);
        $this->addStatusConfiguration($rootChildren);
        $this->addWebSocketConfiguration($rootChildren);
        $this->addRequestSignerConfiguration($rootChildren);

        return $treeBuilder;
    }

    private function addQueueConfiguration(NodeBuilder $rootChildren): void
    {
        $queueConfig = $rootChildren->arrayNode('queue')->addDefaultsIfNotSet()->children();
        $queueConfig
            ->enumNode('use')
            ->values(array_merge(array_keys(Bn01zAsyncTaskExtension::QUEUES), ['custom']))
            ->defaultValue('zmq');
        $queueAdapters = $queueConfig->arrayNode('adapters')->addDefaultsIfNotSet()->children();

        $redisQueueAdapter = $queueAdapters->arrayNode('redis')->addDefaultsIfNotSet()->children();
        $redisQueueAdapter
            ->variableNode('connection')
            ->defaultValue('tcp://127.0.0.1:6379');
        $redisQueueAdapter
            ->scalarNode('queue_name')
            ->defaultValue('task_queue');

        $zmqQueueAdapter = $queueAdapters->arrayNode('zmq')->addDefaultsIfNotSet()->children();
        $zmqQueueAdapter
            ->scalarNode('frontend_connection')
            ->defaultValue('tcp://127.0.0.1:5560');
        $zmqQueueAdapter
            ->scalarNode('backend_connection')
            ->defaultValue(sprintf('ipc://%s/var/bn01z_async_task_workers.ipc', $this->getProjectDir()));
    }

    private function addStatusConfiguration(NodeBuilder $rootChildren): void
    {
        $statusConfig = $rootChildren->arrayNode('status')->addDefaultsIfNotSet()->children();
        $statusConfig
            ->enumNode('use')
            ->values(array_merge(array_keys(Bn01zAsyncTaskExtension::STATUSES), ['custom']))
            ->defaultValue('file');
        $statusAdapters = $statusConfig->arrayNode('adapters')->addDefaultsIfNotSet()->children();

        $fileStatusAdapter = $statusAdapters->arrayNode('file')->addDefaultsIfNotSet()->children();
        $fileStatusAdapter
            ->scalarNode('save_dir')
            ->defaultValue(sprintf('%s/bn01z/async-http/status', $this->getCacheDir()));

        $redisStatusAdapter = $statusAdapters->arrayNode('redis')->addDefaultsIfNotSet()->children();
        $redisStatusAdapter
            ->variableNode('connection')
            ->defaultValue('tcp://127.0.0.1:6379');
    }

    private function addWebSocketConfiguration(NodeBuilder $rootChildren)
    {
        $webSocketConfig = $rootChildren->arrayNode('web_socket')->addDefaultsIfNotSet()->children();
        $webSocketConfig
            ->booleanNode('enabled')
            ->defaultTrue();
        $webSocketConfig
            ->scalarNode('address')
            ->defaultValue('0.0.0.0:8080');
        $webSocketConfig
            ->scalarNode('notificator_connection')
            ->defaultValue('tcp://127.0.0.1:5555');
    }

    private function getCacheDir()
    {
        return $this->parameters->get('kernel.cache_dir');
    }

    private function getProjectDir()
    {
        return $this->parameters->get('kernel.project_dir');
    }

    private function addRequestSignerConfiguration(NodeBuilder $rootChildren)
    {
        $rootChildren
            ->scalarNode('attribute_name')
            ->defaultValue('asyncTaskStatus');
    }
}
