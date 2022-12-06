<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;

class EdgeOrderByClause extends InputObjectType
{

    public function __construct(Relation $relation)
    {
        parent::__construct([
            'name' => '_' . $relation->namekey . 'EdgeOrderByClause',
            'fields' => fn() => [
                'field' => Type::column($relation),
                'order' => Type::get('_SortOrder'),
            ],
        ]);
    }

}
