<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;

class CurrencyEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new CurrencyEnumType();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}