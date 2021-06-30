<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\FileExportType;
use Mrap\GraphCool\Types\Objects\ImportSummaryType;

class ImportSummaryTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new ImportSummaryType();
        self::assertInstanceOf(ObjectType::class, $enum);
    }
}