<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\LocaleEnumType;

class LocaleEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new LocaleEnumType();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}