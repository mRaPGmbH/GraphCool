<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class WhereConditions extends InputObjectType
{

    public function __construct(string $wrappedType)
    {
        if (str_ends_with($wrappedType, 'Edge')) {
            // TODO: make this dynamic
            $column = Type::get('_' . $wrappedType . 'Column');
        } else {
            $column = Type::column($wrappedType);
        }
        parent::__construct([
            'name' => '_' . $wrappedType . 'WhereConditions',
            'fields' => fn() => [
                'column' => $column,
                'operator' => Type::get('_SQLOperator'),
                'value' => Type::get('Mixed'),
                'fulltextSearch' => Type::string(),
                'AND' => Type::listOf($this),
                'OR' => Type::listOf($this)
            ],
        ]);
    }

}
