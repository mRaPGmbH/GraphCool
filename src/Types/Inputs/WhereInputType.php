<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use Mrap\GraphCool\Types\TypeLoader;

class WhereInputType extends InputObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $fields = [
            'column' => $typeLoader->load(substr($name, 0, -15) . 'Column'),
            'operator' => $typeLoader->load('_SQLOperator')(),
            'value' => $typeLoader->load('Mixed'),
            'AND' => new ListOfType($this),
            'OR' => new ListOfType($this)
        ];
        $config = [
            'name' => $name,
            'fields' => $fields
        ];
        parent::__construct($config);
    }

}