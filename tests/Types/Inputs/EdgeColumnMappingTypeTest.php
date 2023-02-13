<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\EdgeColumnMapping;
use function Mrap\GraphCool\model;

class EdgeColumnMappingTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $model = model('DummyModel');
        $enum = new EdgeColumnMapping($model->belongs_to_many);
        self::assertInstanceOf(InputType::class, $enum);
    }
}
