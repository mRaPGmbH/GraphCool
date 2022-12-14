<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\DynamicTypeTrait;

class EdgeReducedColumn extends EnumType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'EdgeReducedColumn';
    }

    public function __construct(Relation $relation)
    {
        parent::__construct([
            'name' => static::getFullName($relation->namekey),
            'description' => 'Pivot properties (prefixed with underscore) of the relation `' . $relation->namekey . '`.',
            'values' => $this->values($relation),
        ]);
    }

    protected function values(Relation $relation): array
    {
        $values = [];
        foreach (Model::relationFieldsForRelation($relation) as $key => $field) {
            $upperName = strtoupper($key);
            $values['_' . $upperName] = [
                'value' => '_' . $key,
                'description' => $field->description ?? null
            ];
        }
        ksort($values);
        return $values;
    }

}
