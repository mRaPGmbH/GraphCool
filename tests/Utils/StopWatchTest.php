<?php


namespace Mrap\GraphCool\Tests\Utils;


use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\Utils\StopWatch;
use Mrap\GraphCool\Tests\TestCase;

class StopWatchTest extends TestCase
{

    public function testStopWatch(): void
    {
        StopWatch::start('name');
        StopWatch::stop('name');
        $times = StopWatch::get();
        self::assertArrayHasKey('name', $times);
    }

    public function testStopWatchReset(): void
    {
        StopWatch::reset();
        $times = StopWatch::get();
        self::assertEmpty($times);
    }

    public function testLogSkippedWhenThresholdDisabled(): void
    {
        putenv('SLOW_QUERY_THRESHOLD=0');
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->never())->method('execute');
        Mysql::setConnector($mock);

        StopWatch::start('request');
        StopWatch::log('a query that would otherwise be logged');

        putenv('SLOW_QUERY_THRESHOLD');
        self::assertTrue(true); // reached without a slow_query insert (asserted via never())
    }

    public function testLogSkippedForFastRequestByDefault(): void
    {
        putenv('SLOW_QUERY_THRESHOLD'); // unset -> default 2000ms
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->never())->method('execute');
        Mysql::setConnector($mock);

        StopWatch::start('request');
        StopWatch::log('a fast query'); // well under 2000ms -> not logged

        self::assertTrue(true);
    }

}
