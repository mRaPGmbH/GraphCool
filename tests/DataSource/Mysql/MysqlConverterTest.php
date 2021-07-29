<?php


namespace Mrap\GraphCool\Tests\DataSource\Mysql;


use Mrap\GraphCool\DataSource\Mysql\MysqlConverter;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Tests\TestCase;

class MysqlConverterTest extends TestCase
{
    public function testConvertDatabaseTypeToOutput(): void
    {
        $compares = [
            ['field' => Field::bool(), 'property' => (object)['value_int' => 1], 'expected' => true],
            ['field' => Field::bool(), 'property' => (object)['value_int' => 0], 'expected' => false],
            ['field' => Field::float(), 'property' => (object)['value_float' => 1.23], 'expected' => 1.23],
            ['field' => Field::int(), 'property' => (object)['value_int' => 7], 'expected' => 7],
            ['field' => Field::date(), 'property' => (object)['value_int' => 8], 'expected' => 8],
            ['field' => Field::dateTime(), 'property' => (object)['value_int' => 9], 'expected' => 9],
            ['field' => Field::time(), 'property' => (object)['value_int' => 10], 'expected' => 10],
            ['field' => Field::timezoneOffset(), 'property' => (object)['value_int' => 11], 'expected' => 11],
            ['field' => Field::decimal(), 'property' => (object)['value_int' => 1234], 'expected' => 12.34],
            ['field' => Field::decimal(4), 'property' => (object)['value_int' => 1234], 'expected' => 0.1234],
            ['field' => Field::string(), 'property' => (object)['value_string' => 'sdf'], 'expected' => 'sdf'],
        ];
        foreach ($compares as $compare) {
            $result = MysqlConverter::convertDatabaseTypeToOutput($compare['field'], $compare['property']);
            self::assertSame($compare['expected'], $result, 'Field type: ' . $compare['field']->type);
        }
    }

    public function testConvertInputTypeToDatabase(): void
    {
        $compares = [
            ['field' => Field::bool(), 'value' => true, 'expected' => 1],
            ['field' => Field::bool(), 'value' => false, 'expected' => 0],
            ['field' => Field::float(), 'value' => 1.23, 'expected' => 1.23],
            ['field' => Field::string()->default('default'), 'value' => null, 'expected' => 'default'],
            ['field' => Field::string()->default('default')->nullable(), 'value' => null, 'expected' => null],
            ['field' => Field::date(), 'value' => 123, 'expected' => 123],
            ['field' => Field::dateTime(), 'value' => 124, 'expected' => 124],
            ['field' => Field::time(), 'value' => 125, 'expected' => 125],
            ['field' => Field::timezoneOffset(), 'value' => 126, 'expected' => 126],
            ['field' => Field::decimal(), 'value' => 12.34, 'expected' => 1234],
            ['field' => Field::decimal(), 'value' => 12.344, 'expected' => 1234],
            ['field' => Field::decimal(), 'value' => 12.345, 'expected' => 1235],
            ['field' => Field::decimal(4), 'value' => 0.1234, 'expected' => 1234],
            ['field' => Field::string(), 'value' => 'sdf', 'expected' => 'sdf'],
        ];
        foreach ($compares as $compare) {
            $result = MysqlConverter::convertInputTypeToDatabase($compare['field'], $compare['value']);
            self::assertSame($compare['expected'], $result, 'Field type: ' . $compare['field']->type);
        }
    }

    public function testConvertInputTypeToDatabaseError(): void
    {
        $this->expectException(\RuntimeException::class);
        $field = Field::string();
        MysqlConverter::convertInputTypeToDatabase($field, null);
    }

    public function testConvertInputTypeToDatabaseTriplet(): void
    {
        $compares = [
            ['field' => Field::bool(), 'value' => true, 'valueInt' => 1, 'valueString' => null, 'valueFloat' => null],
            ['field' => Field::string(), 'value' => 'asdf', 'valueInt' => null, 'valueString' => 'asdf', 'valueFloat' => null],
            ['field' => Field::float(), 'value' => 12.34, 'valueInt' => null, 'valueString' => null, 'valueFloat' => 12.34],
        ];
        foreach ($compares as $compare) {
            [$valueInt, $valueString, $valueFloat] = MysqlConverter::convertInputTypeToDatabaseTriplet($compare['field'], $compare['value']);
            self::assertSame($valueInt, $compare['valueInt']);
            self::assertSame($valueString, $compare['valueString']);
            self::assertSame($valueFloat, $compare['valueFloat']);
        }
    }

    public function testConvertProperties(): void
    {
        require_once($this->dataPath(). '/app/Models/DummyModel.php');
        $model = Model::get('DummyModel');
        $properties = [
            (object)['property' => 'doesnotexist', 'value_int' => 12, 'value_string' => 'asdf', 'value_float' => 0.432],
            (object)['property' => 'last_name', 'value_int' => null, 'value_string' => 'Huber', 'value_float' => null],
            (object)['property' => 'float', 'value_int' => null, 'value_string' => null, 'value_float' => 0.123],
            (object)['property' => 'decimal', 'value_int' => 1234, 'value_string' => null, 'value_float' => null],
            (object)['property' => 'bool', 'value_int' => 1, 'value_string' => null, 'value_float' => null],
        ];
        $result = MysqlConverter::convertProperties($properties, $model);
        $expected = [
            'last_name' => 'Huber',
            'float' => 0.123,
            'decimal' => 12.34,
            'bool' => true,
        ];
        self::assertSame($expected, $result);
    }

    public function testConvertWhereValues(): void
    {
        require_once($this->dataPath(). '/app/Models/DummyModel.php');
        $compares = [
            ['where' => null, 'expected' => null],
            ['where' => [], 'expected' => []],
            ['where' => ['column' => 'last_name'], 'expected' => ['column' => 'last_name']],
            ['where' => ['column' => 'last_name', 'operator' => '='], 'expected' => ['column' => 'last_name', 'operator' => '=']],
            ['where' => ['column' => 'last_name', 'operator' => '=', 'value' => 'Huber'], 'expected' => ['column' => 'last_name', 'operator' => '=', 'value' => 'Huber']],
            ['where' => ['column' => 'last_name', 'operator' => '=', 'value' => 3], 'expected' => ['column' => 'last_name', 'operator' => '=', 'value' => '3']],
            ['where' => ['column' => 'float', 'operator' => '=', 'value' => '3'], 'expected' => ['column' => 'float', 'operator' => '=', 'value' => 3.0]],
            ['where' => ['column' => 'date', 'operator' => 'IN', 'value' => ['2020-01-01', '2021-01-01']], 'expected' => ['column' => 'date', 'operator' => 'IN', 'value' => [1577836800000,1609459200000]]],
            ['where' => ['column' => 'date', 'operator' => '=', 'value' => '2020-01-01'], 'expected' => ['column' => 'date', 'operator' => '=', 'value' => 1577836800000]],
            ['where' => ['AND'=>[['column' => 'last_name', 'operator' => '=', 'value' => 3]]], 'expected' => ['AND'=>[['column' => 'last_name', 'operator' => '=', 'value' => '3']]]],
            ['where' => ['OR'=>[['column' => 'last_name', 'operator' => '=', 'value' => 3]]], 'expected' => ['OR'=>[['column' => 'last_name', 'operator' => '=', 'value' => '3']]]],
        ];
        $model = Model::get('DummyModel');
        foreach ($compares as $compare) {
            $result = MysqlConverter::convertWhereValues($model, $compare['where']);
            self::assertSame($compare['expected'], $result);
        }
    }


}