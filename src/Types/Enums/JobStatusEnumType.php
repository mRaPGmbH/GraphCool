<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Definition\Job;

class JobStatusEnumType extends EnumType
{

    public function __construct()
    {
        $config = [
            'name' => '_Job_Status',
            'description' => 'List of status values of `Job` type.',
            'values' => [
                Job::NEW => ['value' => Job::NEW, 'description' => null],
                Job::RUNNING => ['value' => Job::RUNNING, 'description' => null],
                Job::FINISHED => ['value' => Job::FINISHED, 'description' => null],
                Job::FAILED => ['value' => Job::FAILED, 'description' => null],
            ]
        ];
        parent::__construct($config);
    }
}
