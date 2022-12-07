<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Inputs\ModelColumnMapping;
use Mrap\GraphCool\Types\Inputs\ModelRelation;
use Mrap\GraphCool\Types\Inputs\ModelManyRelation;
use Mrap\GraphCool\Types\Inputs\EdgeOrderByClause;
use Mrap\GraphCool\Types\Inputs\EdgeReducedColumnMapping;
use Mrap\GraphCool\Types\Inputs\EdgeReducedSelector;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeReducedSelectorTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new EdgeReducedSelector('_DummyModel__belongs_to_manyEdgeReducedSelector', new TypeLoader());
        self::assertInstanceOf(InputType::class, $enum);
    }
}