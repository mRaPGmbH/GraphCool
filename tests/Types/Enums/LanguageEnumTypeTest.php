<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\LanguageCode;

class LanguageEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new LanguageCode();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}