<?php

namespace Bn01z\AsyncTask\Tests\EventListener;

use Bn01z\AsyncTask\EventListener\AsyncTaskListener;
use Bn01z\AsyncTask\Queue\TaskQueue;
use Bn01z\AsyncTask\Status\RequestStatusAttributeManager;
use Bn01z\AsyncTask\Status\StatusIdGenerator;
use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Tests\Fixtures\Controller\AsyncController;
use Bn01z\AsyncTask\Tests\Fixtures\Status\NotSerializableStatus;
use Bn01z\AsyncTask\Tests\TestCase;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AsyncTaskListenerTest extends TestCase
{
    private $kernel;
    private $taskQueue;
    private $statusManager;
    private $idGenerator;
    private $attributeManager;
    private $eventListener;
    private $testController;

    protected function setUp()
    {
        AnnotationRegistry::registerLoader('class_exists');

        $this->kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $this->taskQueue = $this->getMockBuilder(TaskQueue::class)->getMock();
        $this->statusManager = $this->getMockBuilder(TaskStatusManager::class)->getMock();
        $this->idGenerator = $this->getMockBuilder(StatusIdGenerator::class)->getMock();
        $this->attributeManager = $this
            ->getMockBuilder(RequestStatusAttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventListener = new AsyncTaskListener(
            $this->taskQueue,
            $this->statusManager,
            $this->idGenerator,
            $this->attributeManager
        );
        $this->testController = new AsyncController();
    }

    public function testAsyncAction()
    {
        $request = $this->createSymfonyRequest();
        $event = new FilterControllerEvent($this->kernel, [$this->testController, 'actionWithAnnotation'], $request, null);
        $status = new TaskStatus('test-id');
        $status->setMessage('queued');

        $this->idGenerator->expects($this->once())
            ->method('generate')->with($request)->willReturn($status->getId());
        $this->statusManager->expects($this->once())
            ->method('create')->with($status->getId())->willReturn($status);
        $this->attributeManager->expects($this->once())
            ->method('set')->with($request, $status);
        $this->taskQueue->expects($this->once())
            ->method('add')->with($request);

        $this->eventListener->onKernelController($event);
        $this->assertTrue(is_callable($event->getController()));

        $statusResponse = ($event->getController())();
        $this->assertInstanceOf(JsonResponse::class, $statusResponse);
        $this->assertEquals(json_encode($status), $statusResponse->getContent());
    }

    public function testRegularAction()
    {
        $request = $this->createSymfonyRequest();
        $event = new FilterControllerEvent($this->kernel, [$this->testController, 'actionWithoutAnnotation'], $request, null);

        $this->idGenerator->expects($this->never())->method('generate');
        $this->statusManager->expects($this->never())->method('create');
        $this->attributeManager->expects($this->never())->method('set');
        $this->taskQueue->expects($this->never())->method('add');

        $this->eventListener->onKernelController($event);
        $this->assertTrue(is_callable($event->getController()));

        $actionResponse = ($event->getController())();
        $this->assertInstanceOf(JsonResponse::class, $actionResponse);
        $this->assertEquals($this->testController->responseNotAsync, $actionResponse);
    }

    public function testNotSupported()
    {
        require_once __DIR__ . '/../Fixtures/Controller/controllerAction.php';
        $request = $this->createSymfonyRequest();
        $event = new FilterControllerEvent($this->kernel, 'controllerAction', $request, null);

        $this->idGenerator->expects($this->never())->method('generate');
        $this->statusManager->expects($this->never())->method('create');
        $this->attributeManager->expects($this->never())->method('set');
        $this->taskQueue->expects($this->never())->method('add');

        $this->eventListener->onKernelController($event);
        $this->assertTrue(is_callable($event->getController()));

        $actionResponse = ($event->getController())();
        $this->assertInstanceOf(JsonResponse::class, $actionResponse);
        $this->assertEquals('"not supported action"', $actionResponse->getContent());
    }
}