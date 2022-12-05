<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class ModelOrderByClause extends InputObjectType
{

    public function __construct(string $name)
    {
        parent::__construct([
            'name' => '_' . $name . 'OrderByClause',
            'fields' => fn() => [
                'field' => Type::column($name),
                'order' => Type::get('_SortOrder'),
            ],
        ]);
    }

}
