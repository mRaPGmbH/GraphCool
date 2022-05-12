<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;

class JobColumnEnumType extends EnumType
{

    public function __construct()
    {
        $config = [
            'name' => '_Job_Column',
            'description' => 'List of column names of `Job` type.',
            'values' => [
                'ID' => ['value' => 'id', 'description' => null],
                'WORKER' => ['value' => 'worker', 'description' => null],
                'STATUS' => ['value' => 'status', 'description' => null],
                'DATA' => ['value' => 'data', 'description' => null],
                'RESULT' => ['value' => 'result', 'description' => null],
                'RUN_AT' => ['value' => 'run_at', 'description' => null],
                'CREATED_AT' => ['value' => 'created_at', 'description' => null],
                'STARTED_AT' => ['value' => 'started_at', 'description' => null],
                'FINISHED_AT' => ['value' => 'finished_at', 'description' => null],
            ]
        ];
        parent::__construct($config);
    }
}
