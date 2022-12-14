<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\Permission;

class PermissionEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new Permission();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}
