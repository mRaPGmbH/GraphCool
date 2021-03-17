<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;


use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeType extends ObjectType
{
    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $names = explode('_', substr($name, 1, -4), 2);
        $key = $names[1];

        $classname = 'App\\Models\\' . $names[0];
        $model = new $classname();
        $relation = $model->$key;
        $type = $typeLoader->load($relation->name);
        $fields = [];
        foreach ($relation as $fieldKey => $field)
        {
            if ($field instanceof Field) {
                $fieldType = $typeLoader->loadForField($field, $names[0] . '_' . $key . '_' . $fieldKey);
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
            'description' => 'A single ' . str_replace('_', '.', substr($name, 1, -4)) . ' relation.',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}