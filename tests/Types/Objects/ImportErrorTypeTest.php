<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ImportErrorType;
use Mrap\GraphCool\Types\Objects\ImportSummaryType;
use Mrap\GraphCool\Types\TypeLoader;

class ImportErrorTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $error = new ImportErrorType();
        self::assertInstanceOf(ObjectType::class, $error);
    }
}