<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use function Mrap\GraphCool\model;

class EdgeColumnType extends EnumType
{

    public function __construct(string $name)
    {
        $names = explode('__', substr($name, 1, -10), 2);
        $parentModel = model($names[0]);

        $relation = $parentModel->{$names[1]};

        $values = [];
        foreach ($parentModel->relationFields($names[1]) as $key => $field) {
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
        $config = [
            'name' => $name,
            'description' => 'Column names of type `' . $relation->name . '` and pivot properties (prefixed with underscore) of the relation `' . $names[0] . '.' . $names[1] . '`.',
            'values' => $values
        ];
        parent::__construct($config);
    }

}