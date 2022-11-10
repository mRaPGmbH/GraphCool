<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;
use function Mrap\GraphCool\model;

class EdgeManyInputType extends InputObjectType
{

    public function __construct(string $name)
    {
        $names = explode('__', substr($name, 1, -12), 2);
        $key = $names[1];

        $model = model($names[0]);
        $relation = $model->$key;

        $fields = [
            'where' => Type::nonNull(Type::get('_' . $relation->name . 'WhereConditions')),
            'search' => Type::string(),
            'searchLoosely' => Type::string(),
            'mode' => Type::get('_RelationUpdateMode'),
        ];
        foreach ($model->relationFields($key) as $fieldKey => $field) {
            if ($field->readonly === false) {
                $fields[$fieldKey] = [
                    'type' => Type::getForField($field, true),
                ];
            }
        }
        $config = [
            'name' => $name,
            'description' => 'Input for many ' . $names[0] . '.' . $key . ' relations using where.',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }


}