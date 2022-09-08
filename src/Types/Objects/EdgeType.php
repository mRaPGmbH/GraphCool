<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Types\TypeLoader;
use function Mrap\GraphCool\model;

class EdgeType extends ObjectType
{
    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $names = explode('__', substr($name, 1, -4), 2);
        $key = $names[1];

        $model = model($names[0]);
        $relation = $model->$key;
        $type = $typeLoader->load($relation->name);
        $fields = [];
        foreach ($relation as $fieldKey => $field) {
            if ($field instanceof Field) {
                $fieldType = $typeLoader->loadForField($field, $names[0] . '__' . $key . '__' . $fieldKey);
                if ($field->null === false) {
                    $fieldType = new NonNull($fieldType);
                }
                $fields[$fieldKey] = [
                    'type' => $fieldType
                ];
            }
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