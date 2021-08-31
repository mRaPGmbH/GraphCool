<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeColumnMappingType extends InputObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $fields = [
            'column' => new NonNull($typeLoader->load(substr($name, 0, -17) . 'EdgeColumn')),
            'label' => Type::string(),
        ];
        $config = [
            'name' => $name,
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}