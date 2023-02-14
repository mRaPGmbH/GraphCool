<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ModelEdge;
use function Mrap\GraphCool\model;

class EdgeTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $model = model('DummyModel');
        $object = new ModelEdge($model->belongs_to_many);
        self::assertInstanceOf(ObjectType::class, $object);
    }
}