<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Definition\Field;

class DynamicEnum extends EnumType
{
    public function __construct(Field $field)
    {
        parent::__construct([
            'name' => '_' . $field->namekey . 'Enum',
            'description' => 'Allowed values for ' . $field->namekey,
            'values' => $this->values($field),
        ]);
    }

    protected function values(Field $field): array
    {
        $values = [];
        foreach ($field->enumValues as $value) {
            $values[$this->sanitizeValue($value)] = ['value' => $value];
        }
        return $values;
    }

    protected function sanitizeValue(string $value): string
    {
        $value = str_replace([' ', '-'], '_', $value);
        $value = (string)preg_replace('/[^a-z_0-9]/i', '', $value);
        return strtoupper($value);
    }

}
