<?php

namespace Mrap\GraphCool\Tests\Types\Scalars;


use GraphQL\Error\Error;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use Mrap\GraphCool\Types\Scalars\TimezoneOffset;
use Mrap\GraphCool\Tests\TestCase;

class TimezoneOffsetTest extends TestCase
{

    public function testSerialize(): void
    {
        $timezoneOffset = new TimezoneOffset();
        $value = 0;
        $result = $timezoneOffset->serialize($value);
        self::assertSame('+00:00', $result);

        $value = 7200;
        $result = $timezoneOffset->serialize($value);
        self::assertSame('+02:00', $result);
    }

    public function testParseValue(): void
    {
        $timezoneOffset = new TimezoneOffset();

        $result = $timezoneOffset->parseValue('Z');
        self::assertSame(0, $result);

        $result = $timezoneOffset->parseValue('+02:00');
        self::assertSame(7200, $result);
    }

    public function testParseLiteral(): void
    {
        $timezoneOffset = new TimezoneOffset();
        $node = new StringValueNode(['value' => 'Z']);
        $result = $timezoneOffset->parseLiteral($node);
        self::assertSame(0, $result);

        $node = new StringValueNode(['value' => '+02:00']);
        $result = $timezoneOffset->parseLiteral($node);
        self::assertSame(7200, $result);
    }

    public function testParseLiteralError(): void
    {
        $node = new NullValueNode([]);
        $this->expectException(Error::class);
        $timezoneOffset = new TimezoneOffset();
        $timezoneOffset->parseLiteral($node);
    }

    public function testCarbonException(): void
    {
        $this->expectException(Error::class);
        $date = new TimezoneOffset();
        $date->parseValue('not a parseable value');
    }

}