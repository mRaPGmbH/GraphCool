<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class EdgeColumnMappingType extends InputObjectType
{

    public function __construct(string $name)
    {
        parent::__construct([
            'name' => $name,
            'fields' => fn() => [
                'column' => Type::nonNull(Type::get(substr($name, 0, -17) . 'EdgeColumn')),
                'label' => Type::string(),
            ],
        ]);
    }

}
