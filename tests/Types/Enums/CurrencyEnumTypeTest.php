<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCode;
use Mrap\GraphCool\Types\Enums\Currency;

class CurrencyEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new Currency();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}