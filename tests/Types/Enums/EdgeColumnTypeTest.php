<?php

namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\EdgeColumnType;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeColumnTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $enum = new EdgeColumnType('_DummyModel__belongs_to_manyEdgeColumn', new TypeLoader());
        self::assertInstanceOf(EnumType::class, $enum);

        $columns = [];
        foreach ($enum->getValues() as $value) {
            self::assertInstanceOf(EnumValueDefinition::class, $value);
            $columns[$value->name] = $value->value;
        }
        $expected = [
            'CREATED_AT' => 'created_at',
            'DATE' => 'date',
            'DATE_TIME' => 'date_time',
            'DELETED_AT' => 'deleted_at',
            'FLOAT' => 'float',
            'ID' => 'id',
            'LAST_NAME' => 'last_name',
            'TIME' => 'time',
            'UPDATED_AT' => 'updated_at',
            'ENUM' => 'enum',
            '_CREATED_AT' => '_created_at',
            '_DELETED_AT' => '_deleted_at',
            '_PIVOT_ENUM' => '_pivot_enum',
            '_PIVOT_PROPERTY' => '_pivot_property',
            '_UPDATED_AT' => '_updated_at',
        ];
        self::assertEquals($expected, $columns);
    }
}