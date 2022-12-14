<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\FileExport;

class FileExportTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new FileExport();
        self::assertInstanceOf(ObjectType::class, $enum);
    }
}