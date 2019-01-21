<?php

namespace Bn01z\AsyncTask\Task;

use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Status\RequestStatusAttributeManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

final class DefaultTaskFactory implements TaskFactory
{
    /**
     * @var ArgumentResolverInterface
     */
    private $argumentResolver;
    /**
     * @var ControllerResolverInterface
     */
    private $controllerResolver;
    /**
     * @var RequestStatusAttributeManager
     */
    private $statusAttributeManager;
    /**
     * @var TaskStatusManager
     */
    private $statusManager;

    public function __construct(
        ControllerResolverInterface $controllerResolver,
        ArgumentResolverInterface $argumentResolver,
        RequestStatusAttributeManager $statusAttributeManager,
        TaskStatusManager $statusManager
    ) {
        $this->argumentResolver = $argumentResolver;
        $this->controllerResolver = $controllerResolver;
        $this->statusAttributeManager = $statusAttributeManager;
        $this->statusManager = $statusManager;
    }

    public function createFromRequest(Request $request): Task
    {
        $status = $this->statusAttributeManager->get($request);
        $controller = $this->createController($request);
        $arguments = $this->argumentResolver->getArguments($request, $controller);

        return new Task($controller, $arguments, new TaskStatusHelper($status, $this->statusManager));
    }

    private function createController(Request $request): callable
    {
        $controller = $this->controllerResolver->getController($request);
        if (!is_callable($controller)) {
            throw new AsyncTaskException('Could not create controller callable');
        }

        return $controller;
    }
}
