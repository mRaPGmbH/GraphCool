<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class WhereConditions extends InputObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'WhereConditions';
    }

    public function __construct(string|Relation $wrappedType)
    {
        parent::__construct([
            'name' => static::getFullName($wrappedType),
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
