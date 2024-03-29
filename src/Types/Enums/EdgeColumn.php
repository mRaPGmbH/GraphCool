<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use function Mrap\GraphCool\model;

class EdgeColumn extends EnumType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'EdgeColumn';
    }

    public function __construct(Relation $relation)
    {
        $config = [
            'name' => static::getFullName($relation->namekey),
            'description' => 'Column names of type `' . $relation->name . '` and pivot properties (prefixed with underscore) of the relation `' . $relation->namekey . '`.',
            'values' => $this->values($relation),
        ];
        parent::__construct($config);
    }

    protected function values(Relation $relation): array
    {
        $values = [];
        foreach (Model::relationFieldsForRelation($relation) as $key => $field) {
            $upperName = strtoupper($key);
            $values['_' . $upperName] = [
                'value' => '_' . $key,
                'description' => $field->description ?? null
            ];
        }

        $model = model($relation->name);
        foreach ($model->fields() as $key => $field) {
            $upperName = strtoupper($key);
            $values[$upperName] = [
                'value' => $key,
                'description' => $field->description ?? null
            ];
        }
        ksort($values);
        return $values;
    }

}
