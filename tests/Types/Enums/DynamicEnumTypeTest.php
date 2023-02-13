<?php

namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\DynamicEnum;
use function Mrap\GraphCool\model;

class DynamicEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = model('DummyModel');
        $enum = new DynamicEnum($model->enum);
        self::assertInstanceOf(EnumType::class, $enum);

        $columns = [];
        foreach ($enum->getValues() as $value) {
            self::assertInstanceOf(EnumValueDefinition::class, $value);
            $columns[$value->name] = $value->value;
        }
        $expected = [
            'A' => 'A',
            'B' => 'B',
            'C' => 'C',
        ];
        self::assertSame($expected, $columns);
    }

    public function testPivotEnum(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = model('DummyModel');
        $enum = new DynamicEnum($model->belongs_to_many->pivot_enum);
        self::assertInstanceOf(EnumType::class, $enum);

        $columns = [];
        foreach ($enum->getValues() as $value) {
            self::assertInstanceOf(EnumValueDefinition::class, $value);
            $columns[$value->name] = $value->value;
        }
        $expected = [
            'X' => 'X',
            'Y' => 'Y',
            'Z' => 'Z',
        ];
        self::assertSame($expected, $columns);
    }
}