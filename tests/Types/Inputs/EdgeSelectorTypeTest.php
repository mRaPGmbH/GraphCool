<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\EdgeSelector;
use function Mrap\GraphCool\model;

class EdgeSelectorTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $model = model('DummyModel');
        $selector = new EdgeSelector($model->belongs_to_many);
        self::assertInstanceOf(InputType::class, $selector);
    }
}