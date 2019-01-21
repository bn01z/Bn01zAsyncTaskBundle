<?php

namespace Bn01z\AsyncTask\Tests\Status;

use Bn01z\AsyncTask\Status\RequestStatusAttributeManager;
use Bn01z\AsyncTask\Status\TaskStatusException;
use Bn01z\AsyncTask\Task\TaskStatus;
use Bn01z\AsyncTask\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestStatusAttributeManagerTest extends TestCase
{
    /**
     * @var string
     */
    private $attributeName;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var RequestStatusAttributeManager
     */
    private $attributeManager;

    protected function setUp()
    {
        $this->attributeName = 'asyncTaskAttribute';
        $this->request = $this->createSymfonyRequest();
        $this->attributeManager = new RequestStatusAttributeManager($this->attributeName);
    }

    public function testSet()
    {
        $taskStatus = new TaskStatus('test-id');
        $this->attributeManager->set($this->request, $taskStatus);
        $this->assertEquals($taskStatus, $this->request->attributes->get($this->attributeName));
    }

    public function testGet()
    {
        $taskStatus = new TaskStatus('test-id');
        $this->request->attributes->set($this->attributeName, $taskStatus);
        $this->assertEquals($taskStatus, $this->attributeManager->get($this->request));
    }

    public function testGetWhenStatusIsMissing()
    {
        $this->expectException(TaskStatusException::class);
        $this->attributeManager->get($this->request);
    }
}
