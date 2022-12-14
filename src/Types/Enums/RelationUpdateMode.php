<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class RelationUpdateMode extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_RelationUpdateMode';
    }

    public const REPLACE = 'REPLACE';
    public const ADD = 'ADD';
    public const REMOVE = 'REMOVE';

    public function __construct()
    {
        $config = [
            'name' => static::getFullName(),
            'description' => 'Define the way in which relations should be updated. Default = ADD',
            'values' => [
                static::REPLACE => [
                    'value' => static::REPLACE,
                    'description' => 'Replace all existing relations with the new ones as defined by the where clauses.'
                ],
                static::ADD => [
                    'value' => static::ADD,
                    'description' => 'Add new relations to the existing ones. If a relation to be added already exists, the existing one will be updated instead.'
                ],
                static::REMOVE => [
                    'value' => static::REMOVE,
                    'description' => 'Remove relations from the existing ones.'
                ]
            ]
        ];
        parent::__construct($config);
    }
}
