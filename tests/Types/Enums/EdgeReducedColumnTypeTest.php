<?php

namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\EdgeReducedColumn;
use function Mrap\GraphCool\model;

class EdgeReducedColumnTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = model('DummyModel');
        $enum = new EdgeReducedColumn($model->belongs_to_many);
        self::assertInstanceOf(EnumType::class, $enum);

        $columns = [];
        foreach ($enum->getValues() as $value) {
            self::assertInstanceOf(EnumValueDefinition::class, $value);
            $columns[$value->name] = $value->value;
        }
        $expected = [
            '_CREATED_AT' => '_created_at',
            '_DELETED_AT' => '_deleted_at',
            '_PIVOT_ENUM' => '_pivot_enum',
            '_PIVOT_PROPERTY' => '_pivot_property',
            '_UPDATED_AT' => '_updated_at',
        ];
        self::assertSame($expected, $columns);
    }
}