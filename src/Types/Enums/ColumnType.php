<?php


namespace Mrap\GraphCool\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\TypeLoader;

class ColumnType extends EnumType
{

    public function __construct(ModelType $type, TypeLoader $typeLoader)
    {
        $classname = 'App\\Models\\' . $type->name;
        $model = new $classname();
        $values = [];
        foreach ($model as $name => $field) {
            if (!$field instanceof Field) {
                continue;
            }
            $upperName = strtoupper($name);
            $values[$upperName] = [
                'value' => $name,
                'description' => $field->description ?? null
            ];
        }
        ksort($values);
        $config = [
            'name' => '_' . $type->name . 'Column',
            'description' => 'Allowed column names for the `where` argument on the query `' . strtolower($type->name). 's`.',
            'values' => $values
        ];
        parent::__construct($config);
    }

}