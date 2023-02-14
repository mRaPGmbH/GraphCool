<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ModelPaginator;

class PaginatorTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new ModelPaginator('DummyModel');
        self::assertInstanceOf(ObjectType::class, $object);
    }

    public function testConstructorJob(): void
    {
        $object = new ModelPaginator('Import_Job');
        self::assertInstanceOf(ObjectType::class, $object);
    }
}