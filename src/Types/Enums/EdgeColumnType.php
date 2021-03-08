<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeColumnType extends EnumType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $names = explode('_', substr($name, 1, -10), 2);
        $key = $names[1];
        $classname = 'App\\Models\\' . $names[0];
        $parentModel = new $classname();

        /** @var Relation $relation */
        $relation = $parentModel->$key;

        $values = [];
        foreach ($relation as $key => $field) {
            if (!$field instanceof Field) {
                continue;
            }
            $upperName = strtoupper($key);
            $values['_'.$upperName] = [
                'value' => '_' . $key,
                'description' => $field->description ?? null
            ];
        }

        $classname = $relation->classname;
        $model = new $classname();

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
            'description' => 'Allowed column names for the `where` argument on the relation `' . $names[0] . '.' . $names[1] . 's`.',
            'values' => $values
        ];
        parent::__construct($config);
    }

}