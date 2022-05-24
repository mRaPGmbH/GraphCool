<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\EntityEnumType;

class EntityEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new EntityEnumType();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}