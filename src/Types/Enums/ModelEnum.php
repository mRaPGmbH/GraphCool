<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;
use Mrap\GraphCool\Utils\ClassFinder;

class ModelEnum extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_Model';
    }

    public function __construct()
    {
        $values = [];
        foreach (ClassFinder::models() as $name => $classname) {
            $label = strtoupper($name);
            $values[$label] = ['value' => $name, 'description' => $name];
        }
        $config = [
            'name' => static::getFullName(),
            'description' => 'List of model names',
            'values' => $values
        ];
        parent::__construct($config);
    }

}
