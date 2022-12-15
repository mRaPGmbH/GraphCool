<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCode;
use Mrap\GraphCool\Types\Enums\Currency;
use Mrap\GraphCool\Types\Inputs\ModelColumnMapping;
use Mrap\GraphCool\Types\TypeLoader;

class ColumnMappingTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new ModelColumnMapping('_DummyModelColumnMapping', new TypeLoader());
        self::assertInstanceOf(InputType::class, $enum);
    }
}