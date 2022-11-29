<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\EdgeType;
use Mrap\GraphCool\Types\Objects\ModelPaginator;
use Mrap\GraphCool\Types\TypeLoader;

class PaginatorTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new ModelPaginator('_DummyModelPaginator', new TypeLoader());
        self::assertInstanceOf(ObjectType::class, $object);
    }

    public function testConstructorJob(): void
    {
        $object = new ModelPaginator('_Import_JobPaginator', new TypeLoader());
        self::assertInstanceOf(ObjectType::class, $object);
    }
}