<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\EdgeType;
use Mrap\GraphCool\Types\Objects\PaginatorInfoType;
use Mrap\GraphCool\Types\TypeLoader;

class PaginatorInfoTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new PaginatorInfoType();
        self::assertInstanceOf(ObjectType::class, $object);
    }

    public function testCreate(): void
    {
        $expected = new \stdClass();
        $expected->count = 3;
        $expected->currentPage = 1;
        $expected->firstItem = 1;
        $expected->hasMorePages = true;
        $expected->lastItem = 101;
        $expected->lastPage = 11;
        $expected->perPage = 10;
        $expected->total = 101;

        $result = PaginatorInfoType::create(3, 1, 10, 101);

        self::assertEquals($expected, $result);
    }

    public function testCreate2(): void
    {
        $expected = new \stdClass();
        $expected->count = 0;
        $expected->currentPage = 1;
        $expected->firstItem = 0;
        $expected->hasMorePages = false;
        $expected->lastItem = 0;
        $expected->lastPage = 1;
        $expected->perPage = 10;
        $expected->total = 0;

        $result = PaginatorInfoType::create(0, 1, 10, 0);

        self::assertEquals($expected, $result);
    }
}