<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;

class EdgeSelector extends InputObjectType
{

    public function __construct(Relation $relation)
    {
        parent::__construct([
            'name' => '_' . $relation->namekey . 'EdgeSelector',
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
