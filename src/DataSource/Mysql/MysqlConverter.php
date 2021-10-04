<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use RuntimeException;
use stdClass;

class MysqlConverter
{
    /**
     * @param Field $field
     * @param mixed $value
     * @return mixed[]
     */
    public static function convertInputTypeToDatabaseTriplet(Field $field, mixed $value, string $key = ''): array
    {
        $return = static::convertInputTypeToDatabase($field, $value, $key);
        return match (gettype($return)) {
            'integer' => [$return, null, null],
            'double' => [null, null, $return], // float
            'string' => [null, $return, null],
            default => [null, null, null]
        };
    }

    public static function convertInputTypeToDatabase(Field $field, mixed $value, string $key = ''): float|int|string|null
    {
        if ($field->null === false && $value === null) {
            $value = $field->default ?? null;
            if ($value === null) {
                throw new RuntimeException('Field may not be null. ' . print_r($field, true));
            }
        }
        if ($value === null) {
            return null;
        }
        return match ($field->type) {
            default => (string)$value,
            Field::DATE, Field::DATE_TIME, Field::TIME, Field::TIMEZONE_OFFSET, Type::BOOLEAN, Type::INT => (int)$value,
            Type::FLOAT => (float)$value,
            Field::DECIMAL => (int)(round($value * (10 ** $field->decimalPlaces))),
            Field::FILE => File::store($key, $value)
        };
    }

    /**
     * @param stdClass[] $properties
     * @param Model|Relation $fieldSource
     * @return mixed[]
     */
    public static function convertProperties(array $properties, Model|Relation $fieldSource): array
    {
        $result = [];
        foreach ($properties as $property) {
            $key = $property->property;
            if (!property_exists($fieldSource, $key)) {
                continue;
            }
            /** @var Field $field */
            $field = $fieldSource->$key;
            $result[$key] = static::convertDatabaseTypeToOutput($field, $property);
        }
        return $result;
    }

    public static function convertDatabaseTypeToOutput(Field $field, stdClass $property, string $key = ''): float|bool|int|string|stdClass
    {
        return match ($field->type) {
            default => (string)$property->value_string,
            Type::BOOLEAN => (bool)$property->value_int,
            Type::FLOAT => (double)$property->value_float,
            Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET => (int)$property->value_int,
            Field::DECIMAL => (float)($property->value_int / (10 ** $field->decimalPlaces)),
            Field::FILE => File::retrieve($key, $property->value_string),
        };
    }

    /**
     * @param Model $model
     * @param mixed[]|null $where
     * @return mixed[]|null
     */
    public static function convertWhereValues(Model $model, ?array &$where): ?array
    {
        if ($where === null) {
            return $where;
        }
        if (isset($where['column']) && array_key_exists('value', $where)) {
            $column = $where['column'];
            /** @var Field $field */
            $field = $model->$column;
            if (is_array($where['value'])) {
                foreach ($where['value'] as $key => $value) {
                    // TODO: is there a better way to do this? are there other types that need special treatment?
                    if ($field->type === Field::DATE || $field->type === Field::TIME || $field->type === Field::DATE_TIME) {
                        $value = strtotime($value) * 1000;
                    }
                    $where['value'][$key] = static::convertInputTypeToDatabase($model->$column, $value);
                }
            } else {
                // TODO: is there a better way to do this? are there other types that need special treatment?
                if ($field->type === Field::DATE || $field->type === Field::TIME || $field->type === Field::DATE_TIME) {
                    $where['value'] = strtotime($where['value']) * 1000;
                }
                $where['value'] = static::convertInputTypeToDatabase($model->$column, $where['value']);
            }
        }
        if (isset($where['AND'])) {
            foreach ($where['AND'] as $key => $subWhere) {
                $where['AND'][$key] = static::convertWhereValues($model, $subWhere);
            }
        }
        if (isset($where['OR'])) {
            foreach ($where['OR'] as $key => $subWhere) {
                $where['OR'][$key] = static::convertWhereValues($model, $subWhere);
            }
        }
        return $where;
    }


}