<?php

namespace Bn01z\AsyncTask\Task;

use Symfony\Component\HttpFoundation\Request;

interface TaskFactory
{
    public function createFromRequest(Request $request): Task;
}
