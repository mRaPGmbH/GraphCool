<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\EdgesType;
use Mrap\GraphCool\Types\TypeLoader;

class EdgesTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new EdgesType('_DummyModel__belongs_to_manyEdges', new TypeLoader());
        self::assertInstanceOf(ObjectType::class, $object);
    }
}