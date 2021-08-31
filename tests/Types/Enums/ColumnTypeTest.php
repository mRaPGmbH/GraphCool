<?php

namespace Mrap\GraphCool\Tests\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\ColumnType;
use Mrap\GraphCool\Types\TypeLoader;

class ColumnTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $enum = new ColumnType('_DummyModelColumn', new TypeLoader());
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
            'COUNTRY' => 'country',
            'TIMEZONE' => 'timezone',
            'CURRENCY' => 'currency',
            'LANGUAGE' => 'language',
            'LOCALE' => 'locale',
            'DECIMAL' => 'decimal',
            'BOOL' => 'bool',
            'UNIQUE' => 'unique'
        ];
        self::assertEquals($expected, $columns);
    }
}