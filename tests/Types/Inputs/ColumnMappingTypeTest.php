<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\ModelColumnMapping;

class ColumnMappingTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new ModelColumnMapping('DummyModel');
        self::assertInstanceOf(InputType::class, $enum);
    }
}