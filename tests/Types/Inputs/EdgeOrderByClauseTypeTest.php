<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\EdgeOrderByClause;
use Mrap\GraphCool\Types\TypeLoader;
use function Mrap\GraphCool\model;

class EdgeOrderByClauseTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $model = model('DummyModel');
        $orderByClause = new EdgeOrderByClause($model->belongs_to_many);
        self::assertInstanceOf(InputType::class, $orderByClause);
    }
}
