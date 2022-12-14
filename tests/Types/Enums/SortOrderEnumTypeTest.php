<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\SortOrder;

class SortOrderEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new SortOrder();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}