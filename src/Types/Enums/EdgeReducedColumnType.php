<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;


use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeReducedColumnType extends EnumType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $names = explode('__', substr($name, 1, -17), 2);
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

        ksort($values);
        $config = [
            'name' => $name,
            'description' => 'Pivot properties (prefixed with underscore) of the relation `' . $names[0] . '.' . $names[1] . '`.',
            'values' => $values
        ];
        parent::__construct($config);
    }

}