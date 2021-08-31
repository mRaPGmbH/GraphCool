<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeInputType extends InputObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $names = explode('__', substr($name, 1, -8), 2);
        $key = $names[1];

        $classname = 'App\\Models\\' . $names[0];
        $model = new $classname();
        $relation = $model->$key;
        $fields = [
            'id' => new NonNull(Type::id())
        ];
        foreach ($relation as $fieldKey => $field) {
            if ($field instanceof Field && $field->readonly === false) {
                $fieldType = $typeLoader->loadForField($field, $names[0] . '__' . $key . '__' . $fieldKey);
                if ($field->null === false) {
                    $fieldType = new NonNull($fieldType);
                }
                $fields[$fieldKey] = [
                    'type' => $fieldType
                ];
            }
        }
        $config = [
            'name' => $name,
            'description' => 'Input for ' . $names[0] . '.' . $key . ' relations.',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }


}