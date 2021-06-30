<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;

class CountryCodeEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new CountryCodeEnumType();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}