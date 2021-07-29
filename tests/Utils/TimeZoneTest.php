<?php


namespace Mrap\GraphCool\Tests\Utils;


use Mrap\GraphCool\Utils\TimeZone;
use Mrap\GraphCool\Tests\TestCase;

class TimeZoneTest extends TestCase
{

    public function testTimeZone(): void
    {
        TimeZone::set(-7200);
        self::assertSame('-02:00', TimeZone::get());
    }

}