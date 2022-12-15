<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use function Mrap\GraphCool\model;

class ModelColumn extends EnumType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'Column';
    }

    public function __construct(string $model)
    {
        parent::__construct([
            'name' => '_' . $model . 'Column',
            'description' => 'List of column names of `' . $model . '` type.',
            'values' => $this->values($model),
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
