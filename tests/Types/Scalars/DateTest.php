<?php

namespace Mrap\GraphCool\Tests\Types\Scalars;


use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use Mrap\GraphCool\Types\Scalars\Date;
use Mrap\GraphCool\Tests\TestCase;

class DateTest extends TestCase
{
    public function testGetObject(): void
    {
        $carbon = new Carbon();
        $value = $carbon->getPreciseTimestamp(3);
        $result = Date::getObject($value);
        self::assertEquals($carbon->getPreciseTimestamp(3), $result->getPreciseTimestamp(3));
    }

    public function testSerialize(): void
    {
        $carbon = new Carbon();
        $value = $carbon->getPreciseTimestamp(3);
        $date = new Date();
        $result = $date->serialize($value);
        self::assertEquals($carbon->format('Y-m-d'), $result);
    }

    public function testParseValue(): void
    {
        $date = new Date();
        self::assertNull($date->parseValue(null));

        $carbon = new Carbon();
        $value = $carbon->toJSON();
        $result = $date->parseValue($value);
        self::assertEquals($carbon->getPreciseTimestamp(3), $result);
    }

    public function testParseLiteral(): void
    {
        $date = new Date();
        $carbon = new Carbon();
        $node = new StringValueNode(['value' => $carbon->toJSON()]);
        $result = $date->parseLiteral($node);
        self::assertEquals($carbon->getPreciseTimestamp(3), $result);
    }

    public function testParseLiteralError(): void
    {
        $node = new NullValueNode([]);
        $this->expectException(Error::class);
        $date = new Date();
        $date->parseLiteral($node);
    }

    public function testCarbonException(): void
    {
        $this->expectException(Error::class);
        $date = new Date();
        $date->parseValue('not a parseable value');
    }
}