<?php

namespace Bn01z\AsyncTask\Tests\Fixtures\Controller;

use Bn01z\AsyncTask\Annotation\Async;
use Symfony\Component\HttpFoundation\JsonResponse;

class AsyncController
{
    public $responseAsync;
    public $responseNotAsync;

    public function __construct()
    {
        $this->responseAsync = JsonResponse::create('OK async');
        $this->responseNotAsync = JsonResponse::create('OK not async');
    }

    /**
     * @return JsonResponse
     * @Async()
     */
    public function actionWithAnnotation()
    {
        return $this->responseAsync;
    }

    /**
     * @return JsonResponse
     */
    public function actionWithoutAnnotation()
    {
        return $this->responseNotAsync;
    }
}