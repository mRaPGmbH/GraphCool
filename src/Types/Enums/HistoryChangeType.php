<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class HistoryChangeType extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_History_ChangeType';
    }

    public function __construct()
    {
        $config = [
            'name' => static::getFullName(),
            'description' => 'List of column names of `History` type.',
            'values' => [
                'CREATE' => ['value' => 'create', 'description' => null],
                'UPDATE' => ['value' => 'update', 'description' => null],
                'MASS_UPDATE' => ['value' => 'massUpdate', 'description' => null],
                'DELETE' => ['value' => 'delete', 'description' => null],
                'RESTORE' => ['value' => 'restore', 'description' => null],
            ]
        ];
        parent::__construct($config);
    }
}
