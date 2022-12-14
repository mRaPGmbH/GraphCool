<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCode;
use Mrap\GraphCool\Types\Enums\Currency;
use Mrap\GraphCool\Types\Enums\SQLOperator;

class SQLOperatorTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new SQLOperator();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}