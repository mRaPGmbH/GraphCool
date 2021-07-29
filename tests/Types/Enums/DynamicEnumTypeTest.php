<?php

namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\DynamicEnumType;
use Mrap\GraphCool\Types\TypeLoader;

class DynamicEnumTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $enum = new DynamicEnumType('_DummyModel__enumEnum', new TypeLoader());
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
        $enum = new DynamicEnumType('_DummyModel__belongs_to_many__pivot_enumEnum', new TypeLoader());
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