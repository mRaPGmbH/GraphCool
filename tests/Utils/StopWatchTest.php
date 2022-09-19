<?php


namespace Mrap\GraphCool\Tests\Utils;


use Mrap\GraphCool\Utils\StopWatch;
use Mrap\GraphCool\Tests\TestCase;

class StopWatchTest extends TestCase
{

    public function testStopWatch(): void
    {
        StopWatch::start('name');
        StopWatch::stop('name');
        $times = StopWatch::get();
        self::assertArrayHasKey('name *1', $times);
    }

    public function testStopWatchReset(): void
    {
        StopWatch::reset();
        $times = StopWatch::get();
        self::assertEmpty($times);
    }

}
