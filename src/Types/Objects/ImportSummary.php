<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\StaticTypeTrait;
use Mrap\GraphCool\Types\Type;

class ImportSummary extends ObjectType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_ImportSummary';
    }

    public function __construct()
    {
        parent::__construct([
            'name' => static::getFullName(),
            'description' => 'Summary of import results, including newly created (inserted), modified existing (updated) and the sum of both (affected).',
            'fields' => fn() => [
                'inserted_rows' => Type::nonNull(Type::int()),
                'inserted_ids' => Type::nonNull(Type::listOf(Type::string())),
                'updated_rows' => Type::nonNull(Type::int()),
                'updated_ids' => Type::nonNull(Type::listOf(Type::string())),
                'affected_rows' => Type::nonNull(Type::int()),
                'affected_ids' => Type::nonNull(Type::listOf(Type::string())),
                'failed_rows' => Type::nonNull(Type::int()),
                'failed_row_numbers' => Type::nonNull(Type::listOf(Type::int())),
                'errors' => Type::listOf(Type::nonNull(Type::importError()))
            ],
        ]);
    }

}
