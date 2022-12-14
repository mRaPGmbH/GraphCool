<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Types\TypeTrait;

class JobType extends ObjectType
{
    public function __construct(string $name)
    {
        parent::__construct([
            'name' => $name,
            'fields' => fn() => [
                'created_at' => Type::nonNull(Type::dateTime()),
                'finished_at' => Type::dateTime(),
                'id' => Type::nonNull(Type::string()),
                'model' => Type::modelEnum(),
                'result' => $this->getResultType($name),
                'run_at' => Type::dateTime(),
                'started_at' => Type::dateTime(),
                'status' => Type::nonNull(Type::jobStatus()),
                'worker' => Type::nonNull(Type::string()),
            ],
        ]);
    }

    protected function getResultType(string $name): FileExportType|ImportSummaryType
    {
        return match($name) {
            'Import' => Type::importSummary(),
            'Export' => Type::fileExport(),
        };
    }

}
