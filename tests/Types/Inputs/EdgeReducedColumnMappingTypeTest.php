<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Inputs\ColumnMappingType;
use Mrap\GraphCool\Types\Inputs\EdgeInputType;
use Mrap\GraphCool\Types\Inputs\EdgeManyInputType;
use Mrap\GraphCool\Types\Inputs\EdgeOrderByClauseType;
use Mrap\GraphCool\Types\Inputs\EdgeReducedColumnMappingType;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeReducedColumnMappingTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new EdgeReducedColumnMappingType('_DummyModel__belongs_to_manyEdgeReducedColumnMapping', new TypeLoader());
        self::assertInstanceOf(InputType::class, $enum);
    }
}