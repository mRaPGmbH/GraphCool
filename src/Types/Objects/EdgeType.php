<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;
use function Mrap\GraphCool\model;

class EdgeType extends ObjectType
{
    public function __construct(string $name)
    {
        $names = explode('__', substr($name, 1, -4), 2);
        $key = $names[1];

        $model = model($names[0]);
        $relation = $model->$key;
        $type = Type::get($relation->name);
        $fields = [];
        foreach ($model->relationFields($key) as $fieldKey => $field) {
            $fields[$fieldKey] = [
                'type' => Type::getForField($field),
            ];
        }
        $fields['_node'] = $type;
        $config = [
            'name' => $name,
            'description' => 'A single ' . substr($name, 1, -4) . ' relation.',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}