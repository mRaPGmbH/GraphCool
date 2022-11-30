<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use function Mrap\GraphCool\model;

class ModelColumn extends EnumType
{

    public function __construct(string $wrappedType)
    {
        parent::__construct([
            'name' => '_' . $wrappedType . 'Column',
            'description' => 'List of column names of `' . $wrappedType . '` type.',
            'values' => $this->values($wrappedType),
        ]);
    }

    protected function values(string $name): array
    {
        $model = model($name);
        $values = [];
        foreach ($model->fields() as $key => $field) {
            $upperName = strtoupper($key);
            $values[$upperName] = [
                'value' => $key,
                'description' => $field->description ?? null
            ];
        }
        ksort($values);
        return $values;
    }

}
