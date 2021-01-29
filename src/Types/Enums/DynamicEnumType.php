<?php


namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Model\Field;

class DynamicEnumType extends EnumType
{
    public function __construct(string $name, Field $field)
    {
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

    protected function sanitizeValue(string $value)
    {
        $value = str_replace([' ', '-'], '_', $value);
        $value = (string) preg_replace('/[^a-z_0-9]/i', '', $value);
        return strtoupper($value);
    }

}