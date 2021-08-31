<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\TypeLoader;

class OrderByClauseType extends InputObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $fields = [
            'field' => $typeLoader->load(substr($name, 0, -13) . 'Column')(),
            'order' => $typeLoader->load('_SortOrder')(),
        ];
        $config = [
            'name' => $name,
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}