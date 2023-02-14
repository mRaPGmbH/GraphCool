<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ImportSummary;

class ImportSummaryTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $summary = new ImportSummary();
        self::assertInstanceOf(ObjectType::class, $summary);
    }
}
