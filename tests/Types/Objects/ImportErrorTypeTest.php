<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ImportError;

class ImportErrorTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $error = new ImportError();
        self::assertInstanceOf(ObjectType::class, $error);
    }
}
