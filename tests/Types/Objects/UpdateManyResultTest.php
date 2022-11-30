<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ModelEdge;
use Mrap\GraphCool\Types\Objects\UpdateManyResult;
use Mrap\GraphCool\Types\TypeLoader;

class UpdateManyResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new UpdateManyResult();
        self::assertInstanceOf(ObjectType::class, $object);
    }
}