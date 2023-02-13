<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\ModelManyRelation;
use function Mrap\GraphCool\model;

class EdgeManyInputTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $model = model('DummyModel');
        $relation = new ModelManyRelation($model->belongs_to_many);
        self::assertInstanceOf(InputType::class, $relation);
    }
}