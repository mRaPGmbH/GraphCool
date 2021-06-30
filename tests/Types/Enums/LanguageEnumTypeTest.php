<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\LanguageEnumType;

class LanguageEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new LanguageEnumType();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}