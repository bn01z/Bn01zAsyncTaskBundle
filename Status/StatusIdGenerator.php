<?php

namespace Bn01z\AsyncTask\Status;

use Symfony\Component\HttpFoundation\Request;

interface StatusIdGenerator
{
    /**
     * @param Request $request
     * @return string
     * @throws TaskStatusException
     */
    public function generate(Request $request): string;
}
