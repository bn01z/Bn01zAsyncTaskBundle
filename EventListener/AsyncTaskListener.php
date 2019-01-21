<?php

namespace Bn01z\AsyncTask\EventListener;

use Bn01z\AsyncTask\Annotation\Async;
use Bn01z\AsyncTask\Queue\TaskQueue;
use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Status\RequestStatusAttributeManager;
use Bn01z\AsyncTask\Status\StatusIdGenerator;
use Bn01z\AsyncTask\Task\AsyncTaskException;
use Bn01z\AsyncTask\Task\TaskStatus;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

final class AsyncTaskListener
{
    /**
     * @var TaskQueue
     */
    private $queue;
    /**
     * @var TaskStatusManager
     */
    private $status;
    /**
     * @var StatusIdGenerator
     */
    private $statusIdGenerator;
    /**
     * @var RequestStatusAttributeManager
     */
    private $statusAttributeManager;

    public function __construct(
        TaskQueue $queue,
        TaskStatusManager $status,
        StatusIdGenerator $statusIdGenerator,
        RequestStatusAttributeManager $statusAttributeManager
    ) {
        $this->queue = $queue;
        $this->status = $status;
        $this->statusIdGenerator = $statusIdGenerator;
        $this->statusAttributeManager = $statusAttributeManager;
    }

    public function onKernelController(FilterControllerEvent $event): void
    {
        if ($this->isControllerIsAsync($event->getController())) {
            $request = $event->getRequest();
            $identifier = $this->statusIdGenerator->generate($request);
            $status = $this->status->create($identifier);
            $this->statusAttributeManager->set($request, $status);
            $event->setController($this->createAcceptedController($status));
            $this->queue->add($request);
        }
    }

    private function createAcceptedController(TaskStatus $status): callable
    {
        return function () use ($status) {
            return JsonResponse::create($status);
        };
    }

    private function isControllerIsAsync(callable $controller): bool
    {
        try {
            if (!is_array($controller)) {
                throw new AsyncTaskException('Library currently supports only actions from controller classes');
            }
            $annotationsReader = new AnnotationReader();
            $reflectionMethod = new \ReflectionMethod(get_class($controller[0]), $controller[1]);
            $async = $annotationsReader->getMethodAnnotation($reflectionMethod, Async::class);

            return $async instanceof Async;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
