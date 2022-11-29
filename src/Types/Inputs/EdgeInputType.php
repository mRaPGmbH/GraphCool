<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;
use function Mrap\GraphCool\model;

class EdgeInputType extends InputObjectType
{

    public function __construct(string $name)
    {
        $names = explode('__', substr($name, 1, -8), 2);
        parent::__construct([
            'name' => $name,
            'description' => 'Input for ' . $names[0] . '.' . $names[1] . ' relations.',
            'fields' => fn() => $this->fieldConfig($names[0], $names[1]),
        ]);
    }

    protected function fieldConfig(string $name, string $key): array
    {
        $model = model($name);
        $fields = [
            'id' => Type::nonNull(Type::id())
        ];
        foreach ($model->relationFields($key) as $fieldKey => $field) {
            if ($field->readonly === false) {
                $fields[$fieldKey] = [
                    'type' => Type::getForField($field, true),
                ];
            }
        }
        return $fields;
    }


}
