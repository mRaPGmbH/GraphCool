<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class EdgeReducedSelectorType extends InputObjectType
{

    public function __construct(string $name)
    {
        parent::__construct([
            'name' => $name,
            'description' => 'Selector for one ' . substr($name, 1, -19) . ' relation.',
            'fields' => fn() => [
                'id' => Type::nonNull(Type::id()),
                'columns' => Type::nonNull(
                    Type::listOf(Type::nonNull(Type::get(substr($name, 0, -15) . 'ReducedColumnMapping')))
                ),
            ],
        ]);
    }


}