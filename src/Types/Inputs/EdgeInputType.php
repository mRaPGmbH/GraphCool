<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NonNull;
use Mrap\GraphCool\Types\Type;
use function Mrap\GraphCool\model;

class EdgeInputType extends InputObjectType
{

    public function __construct(string $name)
    {
        $names = explode('__', substr($name, 1, -8), 2);
        $key = $names[1];

        $model = model($names[0]);
        $fields = [
            'id' => new NonNull(Type::id())
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
            'description' => 'Input for ' . $names[0] . '.' . $key . ' relations.',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }


}