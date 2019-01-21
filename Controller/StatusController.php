<?php

namespace Bn01z\AsyncTask\Controller;

use Bn01z\AsyncTask\Status\TaskStatusException;
use Bn01z\AsyncTask\Status\TaskStatusManager;
use Bn01z\AsyncTask\Task\TaskStatus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StatusController
{
    public function check(string $id, TaskStatusManager $status): JsonResponse
    {
        try {
            $response = JsonResponse::create();
            $data = json_encode($status->get($id));
            if ($data) {
                $response->setJson($data);
            }
            return $response;
        } catch (TaskStatusException $exception) {
            throw new NotFoundHttpException('Status code not found.', $exception);
        }
    }

    public function result(string $id, TaskStatusManager $status)
    {
        try {
            $taskStatus = $status->get($id);
            switch ($taskStatus->getStatus()) {
                case TaskStatus::STATUS_FINISHED:
                    return $taskStatus->getResult();
                case TaskStatus::STATUS_FAILED:
                    return Response::create($taskStatus->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                default:
                    throw new NotFoundHttpException(
                        sprintf('Result for task with id "%s" not available.', $taskStatus->getId())
                    );
            }
        } catch (TaskStatusException $exception) {
            throw new NotFoundHttpException('Status code not found.', $exception);
        }
    }
}
