<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\ModelRelation;
use function Mrap\GraphCool\model;

class EdgeInputTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $model = model('DummyModel');
        $relation = new ModelRelation($model->belongs_to_many);
        self::assertInstanceOf(InputType::class, $relation);
    }
}