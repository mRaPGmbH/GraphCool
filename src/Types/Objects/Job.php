<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class Job extends ObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'Job';
    }

    public function __construct(string $name)
    {
        parent::__construct([
            'name' => static::getFullName($name),
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

    protected function getResultType(string $name): FileExport|ImportSummary|ScalarType
    {
        return match($name) {
            'Import' => Type::importSummary(),
            'Export' => Type::fileExport(),
            'Delete' => Type::boolean(),
        };
    }

}
