<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\SortOrderEnumType;

class SortOrderEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new SortOrderEnumType();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}