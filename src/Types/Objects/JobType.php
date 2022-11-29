<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;

class JobType extends ObjectType
{

    public function __construct(string $name)
    {
        parent::__construct([
            'name' => $name,
            'fields' => fn() => [
                'created_at' => Type::nonNull(Type::get('_DateTime')),
                'finished_at' => Type::get('_DateTime'),
                'id' => Type::nonNull(Type::string()),
                'model' => Type::get('_Model'),
                'result' => Type::get($this->getResultType($name)),
                'run_at' => Type::get('_DateTime'),
                'started_at' => Type::get('_DateTime'),
                'status' => Type::nonNull(Type::get('_Job_Status')),
                'worker' => Type::nonNull(Type::string()),
            ],
        ]);
    }

    protected function getResultType(string $name): string
    {
        return match($name) {
            '_ImportJob' => '_ImportSummary',
            '_ExportJob' => '_FileExport',
        };
    }

}
