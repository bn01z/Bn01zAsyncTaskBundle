<?php

namespace Bn01z\AsyncTask\Tests\DependencyInjection;

use Bn01z\AsyncTask\DependencyInjection\Configuration;
use Bn01z\AsyncTask\Tests\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationTest extends TestCase
{
    private $configuration;
    private $parameterBag;

    protected function setUp()
    {
        $this->parameterBag = $this->getMockBuilder(ParameterBagInterface::class)->getMock();

        $this->configuration = new Configuration($this->parameterBag);
    }

    public function testGetConfigTreeBuilderDefault()
    {
        $this->parameterBag
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['kernel.project_dir'], ['kernel.cache_dir'])
            ->willReturnOnConsecutiveCalls('./tmp/project', './tmp/cache');

        $processor = new Processor();

        $config = $processor->processConfiguration($this->configuration, []);
        $this->assertEquals(self::getDefaultConfig(), $config);

    }

    public function testGetConfigTreeBuilderCustom()
    {
        $this->parameterBag
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['kernel.project_dir'], ['kernel.cache_dir'])
            ->willReturnOnConsecutiveCalls('./tmp/project', './tmp/cache');

        $customConfig = [
            'queue' => ['use' => 'redis'],
            'status' => ['use' => 'redis'],
        ];
        $processor = new Processor();

        $config = $processor->processConfiguration($this->configuration, [$customConfig]);
        $expectedConfig = self::getDefaultConfig();
        $expectedConfig['queue']['use'] = 'redis';
        $expectedConfig['status']['use'] = 'redis';

        $this->assertEquals($expectedConfig, $config);

    }

    protected static function getDefaultConfig()
    {
        return [
            'queue' =>
                [
                    'use' => 'zmq',
                    'adapters' =>
                        [
                            'redis' =>
                                [
                                    'connection' => 'tcp://127.0.0.1:6379',
                                    'queue_name' => 'task_queue',
                                ],
                            'zmq' =>
                                [
                                    'frontend_connection' => 'tcp://127.0.0.1:5560',
                                    'backend_connection' => 'ipc://./tmp/project/var/bn01z_async_task_workers.ipc',
                                ],
                        ],
                ],
            'status' =>
                [
                    'use' => 'file',
                    'adapters' =>
                        [
                            'file' =>
                                [
                                    'save_dir' => './tmp/cache/bn01z/async-http/status',
                                ],
                            'redis' =>
                                [
                                    'connection' => 'tcp://127.0.0.1:6379',
                                ],
                        ],
                ],
            'web_socket' =>
                [
                    'enabled' => true,
                    'address' => '0.0.0.0:8080',
                    'notificator_connection' => 'tcp://127.0.0.1:5555',
                ],
            'attribute_name' => 'asyncTaskStatus',
        ];
    }

}
