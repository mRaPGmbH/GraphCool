<?php

namespace Mrap\GraphCool\Tests\Types\Scalars;


use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use Mrap\GraphCool\Types\Scalars\Time;
use Mrap\GraphCool\Tests\TestCase;

class TimeTest extends TestCase
{
    public function testGetObject(): void
    {
        $carbon = new Carbon();
        $value = $carbon->getPreciseTimestamp(3);
        $result = Time::getObject($value);
        self::assertSame($carbon->getPreciseTimestamp(3), $result->getPreciseTimestamp(3));
    }

    public function testSerialize(): void
    {
        $carbon = new Carbon();
        $value = $carbon->getPreciseTimestamp(3);
        $time = new Time();
        $result = new Carbon($time->serialize($value));

        // float rounding errors may cause 1ms difference
        self::assertSame($carbon->format('H:i:sp'), $result->format('H:i:sp'));
    }

    public function testParseValue(): void
    {
        $time = new Time();
        self::assertNull($time->parseValue(null));

        $carbon = new Carbon();
        $value = $carbon->toJSON();
        $result = $time->parseValue($value);
        self::assertSame((int)$carbon->getPreciseTimestamp(3), $result);
    }

    public function testParseLiteral(): void
    {
        $time = new Time();
        $carbon = new Carbon();
        $node = new StringValueNode(['value' => $carbon->toJSON()]);
        $result = $time->parseLiteral($node);
        self::assertSame((int)$carbon->getPreciseTimestamp(3), $result);
    }

    public function testParseLiteralError(): void
    {
        $node = new NullValueNode([]);
        $this->expectException(Error::class);
        $time = new Time();
        $time->parseLiteral($node);
    }

    public function testCarbonException(): void
    {
        $this->expectException(Error::class);
        $date = new Time();
        $date->parseValue('not a parseable value');
    }

}