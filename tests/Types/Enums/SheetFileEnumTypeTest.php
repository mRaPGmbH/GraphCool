<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCode;
use Mrap\GraphCool\Types\Enums\Currency;
use Mrap\GraphCool\Types\Enums\SheetFile;

class SheetFileEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new SheetFile();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}