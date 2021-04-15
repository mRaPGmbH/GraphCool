<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;

class RelationUpdateModeEnum extends EnumType
{

    public function __construct()
    {
        $config = [
            'name' => '_RelationUpdateMode',
            'description' => 'Define the way in which relations should be updated. Default = ADD',
            'values' => [
                'REPLACE' => ['value' => 'REPLACE', 'description' => 'Replace all existing relations with the new ones as defined by the where clauses.'],
                'ADD' => ['value' => 'ADD', 'description' => 'Add new relations to the existing ones. If a relation to be added already exists, the existing one will be updated instead.'],
                'REMOVE' => ['value' => 'REMOVE', 'description' => 'Remove relations from the existing ones.']
            ]
        ];
        parent::__construct($config);
    }
}