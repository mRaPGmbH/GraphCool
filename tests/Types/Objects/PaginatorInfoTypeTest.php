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
}