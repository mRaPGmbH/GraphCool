<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ImportSummary;
use Mrap\GraphCool\Types\TypeLoader;

class ImportSummaryTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new ImportSummary(new TypeLoader());
        self::assertInstanceOf(ObjectType::class, $enum);
    }
}