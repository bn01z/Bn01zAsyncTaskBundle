<?php

namespace Bn01z\AsyncTask\Status;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

final class Uuid4StatusIdGenerator implements StatusIdGenerator
{
    public function generate(Request $request): string
    {
        return Uuid::uuid4()->toString();
    }
}
