<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Types\TypeLoader;
use function Mrap\GraphCool\model;

class EdgeReducedColumnType extends EnumType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $names = explode('__', substr($name, 1, -17), 2);
        $key = $names[1];
        $parentModel = model($names[0]);

        $relation = $parentModel->$key;

        $values = [];
        foreach (get_object_vars($relation) as $key => $field) {
            if (!$field instanceof Field) {
                continue;
            }
            $upperName = strtoupper($key);
            $values['_' . $upperName] = [
                'value' => '_' . $key,
                'description' => $field->description ?? null
            ];
        }

        ksort($values);
        $config = [
            'name' => $name,
            'description' => 'Pivot properties (prefixed with underscore) of the relation `' . $names[0] . '.' . $names[1] . '`.',
            'values' => $values
        ];
        parent::__construct($config);
    }

}