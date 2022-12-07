<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;

class EdgeReducedColumn extends EnumType
{

    public function __construct(Relation $relation)
    {
        parent::__construct([
            'name' => '_' . $relation->namekey . 'EdgeReducedColumn',
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
