<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ModelEdge;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new ModelEdge('_DummyModel__belongs_to_manyEdge', new TypeLoader());
        self::assertInstanceOf(ObjectType::class, $object);
    }
}