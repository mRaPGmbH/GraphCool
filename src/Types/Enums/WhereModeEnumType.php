<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;

class WhereModeEnumType extends EnumType
{
    public function __construct()
    {
        $config = [
            'name' => '_WhereMode',
            'description' => 'Define how `where` and `whereHas` are combined. Default: AND',
            'values' => [
                'AND' => ['value' => 'AND', 'description' => 'require all wheres to match'],
                'OR' => ['value' => 'OR', 'description' => 'require only one where to match']
            ]
        ];
        parent::__construct($config);
    }
}