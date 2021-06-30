<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\TypeLoader;

class DynamicEnumType extends EnumType
{
    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $names = explode('__', substr($name, 1, -4), 3);
        $key = $names[1];

        $classname = 'App\\Models\\' . $names[0];
        $model = new $classname();

        $field = $model->$key;
        if (isset($names[2]) && $field instanceof Relation) {
            $key = $names[2];
            $field = $field->$key;
        }

        $values = [];
        foreach ($field->enumValues as $value) {
            $values[$this->sanitizeValue($value)] = ['value' => $value];
        }
        $config = [
            'name'        => $name,
            'description' => 'Allowed values for ' . substr($name, 0 , -4),
            'values'      => $values,
        ];
        parent::__construct($config);
    }

    protected function sanitizeValue(string $value): string
    {
        $value = str_replace([' ', '-'], '_', $value);
        $value = (string) preg_replace('/[^a-z_0-9]/i', '', $value);
        return strtoupper($value);
    }

}