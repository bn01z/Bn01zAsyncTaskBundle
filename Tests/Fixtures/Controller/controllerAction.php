<?php

use Bn01z\AsyncTask\Annotation\Async;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @return JsonResponse
 * @Async()
 */
function controllerAction()
{
    return JsonResponse::create('not supported action');
}