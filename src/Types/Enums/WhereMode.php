<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class WhereMode extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_WhereMode';
    }

    public function __construct()
    {
        $config = [
            'name' => static::getFullName(),
            'description' => 'Define how `where` and `whereHas` are combined. Default: AND',
            'values' => [
                'AND' => ['value' => 'AND', 'description' => 'require all wheres to match'],
                'OR' => ['value' => 'OR', 'description' => 'require only one where to match']
            ]
        ];
        parent::__construct($config);
    }
}
