<?php

namespace Bn01z\AsyncTask\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TestCase extends PHPUnitTestCase
{
    protected function createSymfonyRequest(): Request
    {
        $request = Request::create('/');
        $request->setSession($this->getMockBuilder(SessionInterface::class)->getMock());
        return $request;
    }
}
