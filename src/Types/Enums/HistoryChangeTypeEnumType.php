<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;

class HistoryChangeTypeEnumType extends EnumType
{

    public function __construct()
    {
        $config = [
            'name' => '_History_ChangeType',
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
