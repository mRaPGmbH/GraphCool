<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Utils\ClassFinder;

class EntityEnum extends EnumType
{
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
            'name' => '_Entity',
            'description' => 'List of available entities.',
            'values' => $values
        ];
        parent::__construct($config);
    }

}