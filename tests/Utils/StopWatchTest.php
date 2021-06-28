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
        [$times, $percentages] = StopWatch::get();
        self::assertArrayHasKey('name', $times);
        self::assertArrayHasKey('name', $percentages);
    }
}