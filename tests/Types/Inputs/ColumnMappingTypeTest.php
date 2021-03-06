<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Inputs\ColumnMappingType;
use Mrap\GraphCool\Types\TypeLoader;

class ColumnMappingTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new ColumnMappingType('_DummyModelColumnMapping', new TypeLoader());
        self::assertInstanceOf(InputType::class, $enum);
    }
}