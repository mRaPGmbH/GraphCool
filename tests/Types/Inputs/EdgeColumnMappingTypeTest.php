<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\EdgeColumnMappingType;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeColumnMappingTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $enum = new EdgeColumnMappingType('_DummyModel__belongs_to_manyColumnMapping', new TypeLoader());
        self::assertInstanceOf(InputType::class, $enum);
    }
}