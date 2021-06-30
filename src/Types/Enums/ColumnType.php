<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Types\TypeLoader;

class ColumnType extends EnumType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $typeName = substr($name, 1, -6);
        $classname = 'App\\Models\\' . $typeName;
        $model = new $classname();
        $values = [];
        foreach ($model as $key => $field) {
            if (!$field instanceof Field) {
                continue;
            }
            $upperName = strtoupper($key);
            $values[$upperName] = [
                'value' => $key,
                'description' => $field->description ?? null
            ];
        }
        ksort($values);
        $config = [
            'name' => $name,
            'description' => 'List of column names of `' . $typeName. '` type.',
            'values' => $values
        ];
        parent::__construct($config);
    }

}