<?php

namespace Bn01z\AsyncTask\Queue;

interface Runnable
{
    /**
     * @throws TaskQueueException
     */
    public function run(): void;
}
