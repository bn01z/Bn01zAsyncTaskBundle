<?php

namespace Bn01z\AsyncTask\Tests\Controller;

use Bn01z\AsyncTask\Controller\StatusController;
use Bn01z\AsyncTask\Status\TaskStatusException;
use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Tests\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StatusControllerTest extends TestCase
{
    private $controller;
    private $statusManager;
    private $taskStatus;

    protected function setUp()
    {
        $this->controller = new StatusController();
        $this->taskStatus = new TaskStatus('test-id');
        $this->taskStatus->setResult('task result');
        $this->statusManager = $this->getMockBuilder(TaskStatusManager::class)->getMock();
    }

    public function testStatusCheck()
    {
        $this->statusManager
            ->expects($this->once())
            ->method('get')
            ->with($this->taskStatus->getId())
            ->willReturn($this->taskStatus);

        $response = $this->controller->check($this->taskStatus->getId(), $this->statusManager);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode($this->taskStatus), $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testStatusCheckMissingStatus()
    {
        $this->statusManager
            ->expects($this->once())
            ->method('get')
            ->with($this->anything())
            ->willThrowException(new TaskStatusException());
        $this->expectException(NotFoundHttpException::class);

        $this->controller->check($this->taskStatus->getId(), $this->statusManager);
    }

    public function testStatusResult()
    {
        $this->taskStatus->setStatus(TaskStatus::STATUS_FINISHED);
        $this->statusManager
            ->expects($this->once())
            ->method('get')
            ->with($this->taskStatus->getId())
            ->willReturn($this->taskStatus);

        $response = $this->controller->result($this->taskStatus->getId(), $this->statusManager);

        $this->assertEquals($this->taskStatus->getResult(), $response);
    }

    public function testStatusResultFailed()
    {
        $this->taskStatus->setStatus(TaskStatus::STATUS_FAILED)->setMessage('failed test');
        $this->statusManager
            ->expects($this->once())
            ->method('get')
            ->with($this->taskStatus->getId())
            ->willReturn($this->taskStatus);

        $response = $this->controller->result($this->taskStatus->getId(), $this->statusManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($this->taskStatus->getMessage(), $response->getContent());
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testStatusResultNotFinished()
    {
        $this->statusManager
            ->expects($this->once())
            ->method('get')
            ->with($this->taskStatus->getId())
            ->willReturn($this->taskStatus);
        $this->expectException(NotFoundHttpException::class);

        $this->controller->result($this->taskStatus->getId(), $this->statusManager);
    }

    public function testStatusResultMissingStatus()
    {
        $this->statusManager
            ->expects($this->once())
            ->method('get')
            ->with($this->anything())
            ->willThrowException(new TaskStatusException());
        $this->expectException(NotFoundHttpException::class);

        $this->controller->result($this->taskStatus->getId(), $this->statusManager);
    }
}