<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\LocaleCode;

class LocaleEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new LocaleCode();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}