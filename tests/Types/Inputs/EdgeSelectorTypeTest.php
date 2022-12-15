<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCode;
use Mrap\GraphCool\Types\Enums\Currency;
use Mrap\GraphCool\Types\Inputs\ModelColumnMapping;
use Mrap\GraphCool\Types\Inputs\ModelRelation;
use Mrap\GraphCool\Types\Inputs\ModelManyRelation;
use Mrap\GraphCool\Types\Inputs\EdgeOrderByClause;
use Mrap\GraphCool\Types\Inputs\EdgeReducedColumnMapping;
use Mrap\GraphCool\Types\Inputs\EdgeReducedSelector;
use Mrap\GraphCool\Types\Inputs\EdgeSelector;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeSelectorTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new EdgeSelector('_DummyModel__belongs_to_manyEdgeSelector', new TypeLoader());
        self::assertInstanceOf(InputType::class, $enum);
    }
}