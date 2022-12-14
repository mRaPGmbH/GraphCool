<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;
use Mrap\GraphCool\Utils\ClassFinder;

class Entity extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_Entity';
    }

    public function __construct()
    {
        $values = [
            '_EXPORTJOB' => ['value' => '_ExportJob', 'description' => '_ExportJob'],
            '_IMPORTJOB' => ['value' => '_ImportJob', 'description' => '_ImportJob'],
        ];
        foreach (ClassFinder::models() as $name => $classname) {
            $values[strtoupper($name)] = [
                'value' => $name,
                'description' => $name
            ];
        }
        ksort($values);
        $config = [
            'name' => static::getFullName(),
            'description' => 'List of available entities.',
            'values' => $values
        ];
        parent::__construct($config);
    }

}
