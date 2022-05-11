<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Types\TypeLoader;

class JobType extends ObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $config = [
            'name' => $name,
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'status' => Type::nonNull(Type::string()),
                'result' => $typeLoader->load($this->getResultType($name)),
                'run_at' => $typeLoader->load('_DateTime'),
                'created_at' => Type::nonNull($typeLoader->load('_DateTime')),
                'started_at' => $typeLoader->load('_DateTime'),
                'finished_at' => $typeLoader->load('_DateTime'),
            ],
        ];
        ksort($config['fields']);
        parent::__construct($config);
    }

    protected function getResultType(string $name): string
    {
        return match($name) {
            '_ImportJob' => '_ImportSummary',
            '_ExportJob' => '_FileExport',
        };
    }


}