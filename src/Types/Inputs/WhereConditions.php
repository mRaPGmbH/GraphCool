<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class WhereConditions extends InputObjectType
{

    public function __construct(string $wrappedType)
    {
        parent::__construct([
            'name' => '_' . $wrappedType . 'WhereConditions',
            'fields' => fn() => [
                'column' => Type::get('_' . $wrappedType . 'Column'),
                'operator' => Type::get('_SQLOperator'),
                'value' => Type::get('Mixed'),
                'fulltextSearch' => Type::string(),
                'AND' => Type::listOf($this),
                'OR' => Type::listOf($this)
            ],
        ]);
    }

}
