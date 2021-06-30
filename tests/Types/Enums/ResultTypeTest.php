<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Enums\ResultType;

class ResultTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new ResultType();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}