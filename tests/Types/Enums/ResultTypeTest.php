<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCode;
use Mrap\GraphCool\Types\Enums\Currency;
use Mrap\GraphCool\Types\Enums\Result;

class ResultTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new Result();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}