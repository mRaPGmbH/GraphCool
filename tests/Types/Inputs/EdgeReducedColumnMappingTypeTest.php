<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\EdgeReducedColumnMapping;
use function Mrap\GraphCool\model;

class EdgeReducedColumnMappingTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $model = model('DummyModel');
        $columnMapping = new EdgeReducedColumnMapping($model->belongs_to_many);
        self::assertInstanceOf(InputType::class, $columnMapping);
    }
}