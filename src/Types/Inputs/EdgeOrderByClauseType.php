<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeOrderByClauseType extends InputObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $fields = [
            'field' => $typeLoader->load(substr($name, 0, -17) . 'EdgeColumn'),
            'order' => $typeLoader->load('_SortOrder'),
        ];
        $config = [
            'name' => $name,
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}