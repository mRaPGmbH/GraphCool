<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class EdgeOrderByClauseType extends InputObjectType
{

    public function __construct(string $name)
    {
        parent::__construct([
            'name' => $name,
            'fields' => fn() => [
                'field' => Type::get(substr($name, 0, -17) . 'EdgeColumn'),
                'order' => Type::get('_SortOrder'),
            ],
        ]);
    }

}