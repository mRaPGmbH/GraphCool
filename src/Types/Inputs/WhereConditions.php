<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;

class WhereConditions extends InputObjectType
{

    public function __construct(string|Relation $wrappedType)
    {
        if (is_string($wrappedType)) {
            $name = '_' . $wrappedType . 'WhereConditions';
        } else {
            $name = '_' . $wrappedType->namekey . 'EdgeWhereConditions';
        }
        parent::__construct([
            'name' => $name,
            'fields' => fn() => [
                'column' => Type::column($wrappedType),
                'operator' => Type::sqlOperator(),
                'value' => Type::mixed(),
                'fulltextSearch' => Type::string(),
                'AND' => Type::listOf($this),
                'OR' => Type::listOf($this)
            ],
        ]);
    }

}
