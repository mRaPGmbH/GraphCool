<?php


namespace Mrap\GraphCool\Types;


use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Model\Field;

class EdgeType extends ObjectType
{
    public function __construct(string $key, ModelType $parentType, TypeLoader $typeLoader)
    {
        $classname = 'App\\Models\\' . $parentType->name;
        $model = new $classname();
        $relation = $model->$key;
        $type = $typeLoader->load($relation->name);
        $fields = [];
        foreach ($relation as $fieldKey => $field)
        {
            if ($field instanceof Field) {
                $fieldType = $typeLoader->loadForField($field, $fieldKey);
                if ($field->null === false) {
                    $fieldType = new NonNull($type);
                }
                $fields[$fieldKey] = [
                    'type' => $fieldType
                ];
            }
        }
        $fields['_node'] = $type;
        $config = [
            'name' => '_' . $parentType->name . '_' . $key . 'Edge',
            'description' => 'A paginated list of ' . $key . ' items.',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}