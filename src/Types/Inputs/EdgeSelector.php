<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class EdgeSelector extends InputObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'EdgeSelector';
    }

    public function __construct(Relation $relation)
    {
        parent::__construct([
            'name' => static::getFullName($relation->namekey),
            'description' => 'Selector for one ' . $relation->namekey . ' relation.',
            'fields' => fn() => [
                'id' => Type::nonNull(Type::id()),
                'columns' => Type::nonNull(
                    Type::listOf(Type::nonNull(Type::columnMapping($relation)))
                ),
            ],
        ]);
    }


}
