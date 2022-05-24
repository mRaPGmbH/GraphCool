<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\PermissionEnumType;

class PermissionEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new PermissionEnumType();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}
