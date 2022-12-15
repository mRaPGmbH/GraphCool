<?php


namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\JobColumn;

class JobColumnEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new JobColumn();
        self::assertInstanceOf(EnumType::class, $enum);
    }
}
