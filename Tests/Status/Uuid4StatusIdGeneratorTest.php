<?php

namespace Bn01z\AsyncTask\Tests\Status;

use Bn01z\AsyncTask\Status\Uuid4StatusIdGenerator;
use Bn01z\AsyncTask\Tests\TestCase;

class Uuid4StatusIdGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $idPattern = '/^[\da-f]{8}\-[\da-f]{4}\-[\da-f]{4}\-[\da-f]{4}\-[\da-f]{12}$/';
        $request = $this->createSymfonyRequest();
        $generator = new Uuid4StatusIdGenerator();
        $this->assertRegExp($idPattern, $generator->generate($request));
    }
}