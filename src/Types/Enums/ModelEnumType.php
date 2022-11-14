<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Utils\ClassFinder;

class ModelEnumType extends EnumType
{

    public function __construct()
    {
        $values = [];
        foreach (ClassFinder::models() as $name => $classname) {
            $label = strtoupper($name);
            $values[$label] = ['value' => $name, 'description' => $name];
        }
        $config = [
            'name' => '_Model',
            'description' => 'List of model names',
            'values' => $values
        ];
        parent::__construct($config);
    }

}
