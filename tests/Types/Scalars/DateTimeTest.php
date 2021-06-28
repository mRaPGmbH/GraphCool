<?php

namespace Mrap\GraphCool\Tests\Types\Scalars;


use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use Mrap\GraphCool\Types\Scalars\DateTime;
use Mrap\GraphCool\Tests\TestCase;

class DateTimeTest extends TestCase
{
    public function testGetObject(): void
    {
        $carbon = new Carbon();
        $value = $carbon->getPreciseTimestamp(3);
        $result = DateTime::getObject($value);
        self::assertEquals($carbon->getPreciseTimestamp(3), $result->getPreciseTimestamp(3));
    }

    public function testSerialize(): void
    {
        $carbon = new Carbon();
        $value = $carbon->getPreciseTimestamp(3);
        $dateTime = new DateTime();
        $result = new Carbon($dateTime->serialize($value));

        // float rounding errors may cause 1ms difference
        self::assertEquals($carbon->format('Y-m-d\TH:i:sp'), $result->format('Y-m-d\TH:i:sp'));
    }

    public function testParseValue(): void
    {
        $dateTime = new DateTime();
        self::assertNull($dateTime->parseValue(null));

        $carbon = new Carbon();
        $value = $carbon->toJSON();
        $result = $dateTime->parseValue($value);
        self::assertEquals($carbon->getPreciseTimestamp(3), $result);
    }

    public function testParseLiteral(): void
    {
        $dateTime = new DateTime();
        $carbon = new Carbon();
        $node = new StringValueNode(['value' => $carbon->toJSON()]);
        $result = $dateTime->parseLiteral($node);
        self::assertEquals($carbon->getPreciseTimestamp(3), $result);
    }

    public function testParseLiteralError(): void
    {
        $node = new NullValueNode([]);
        $this->expectException(Error::class);
        $dateTime = new DateTime();
        $dateTime->parseLiteral($node);
    }
}