<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Enums\RelationUpdateModeEnum;

class RelationUpdateModeEnumTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new RelationUpdateModeEnum();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}