<?php

namespace Bn01z\AsyncTask\Tests\Task;

use Bn01z\AsyncTask\Status\RequestStatusAttributeManager;
use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Task\AsyncTaskException;
use Bn01z\AsyncTask\Task\Task;
use Bn01z\AsyncTask\Task\DefaultTaskFactory;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class DefaultTaskFactoryTest extends TestCase
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var TaskStatus
     */
    private $taskStatus;
    /**
     * @var ControllerResolverInterface
     */
    private $controllerResolver;
    /**
     * @var ArgumentResolverInterface
     */
    private $argumentResolver;
    /**
     * @var RequestStatusAttributeManager
     */
    private $requestStatusAttributeManager;
    /**
     * @var TaskStatusManager
     */
    private $taskStatusManager;
    /**
     * @var DefaultTaskFactory
     */
    private $taskFactory;

    public function setUp()
    {
        $this->request = $this->createSymfonyRequest();
        $this->taskStatus = new TaskStatus('test-id');
        $this->controllerResolver = $this->getMockBuilder(ControllerResolverInterface::class)->getMock();
        $this->argumentResolver = $this->getMockBuilder(ArgumentResolverInterface::class)->getMock();
        $this->requestStatusAttributeManager = $this
            ->getMockBuilder(RequestStatusAttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->taskStatusManager = $this->getMockBuilder(TaskStatusManager::class)->getMock();

        $this->taskFactory = new DefaultTaskFactory(
            $this->controllerResolver,
            $this->argumentResolver,
            $this->requestStatusAttributeManager,
            $this->taskStatusManager
        );
    }

    public function testCreateFromRequest()
    {
        $controllerAction = function ($test) {
            return $test;
        };

        $this->controllerResolver->expects($this->once())
            ->method('getController')->with($this->request)->willReturn($controllerAction);
        $this->argumentResolver->expects($this->once())
            ->method('getArguments')->with($this->request, $controllerAction)->willReturn(['test']);
        $this->requestStatusAttributeManager->expects($this->once())
            ->method('get')->with($this->request)->willReturn($this->taskStatus);

        $this->assertInstanceOf(Task::class, $this->taskFactory->createFromRequest($this->request));
    }

    public function testInvalidFromRequest()
    {
        $controllerAction = false;

        $this->controllerResolver->expects($this->once())
            ->method('getController')->with($this->request)->willReturn($controllerAction);
        $this->argumentResolver->expects($this->never())
            ->method('getArguments')->with($this->request, $controllerAction)->willReturn(['test']);
        $this->requestStatusAttributeManager->expects($this->once())
            ->method('get')->with($this->request)->willReturn($this->taskStatus);

        $this->expectException(AsyncTaskException::class);
        $this->taskFactory->createFromRequest($this->request);
    }
}
