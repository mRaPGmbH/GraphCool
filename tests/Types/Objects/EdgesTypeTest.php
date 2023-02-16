<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ModelEdge;
use Mrap\GraphCool\Types\Objects\ModelEdgePaginator;
use function Mrap\GraphCool\model;

class EdgesTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $model = model('DummyModel');
        $edge = new ModelEdge($model->belongs_to_many);
        $object = new ModelEdgePaginator($edge);
        self::assertInstanceOf(ObjectType::class, $object);
    }
}
