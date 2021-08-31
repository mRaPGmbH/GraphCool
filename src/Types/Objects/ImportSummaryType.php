<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Types\TypeLoader;

class ImportSummaryType extends ObjectType
{

    public function __construct(TypeLoader $typeLoader)
    {
        $config = [
            'name' => '_ImportSummary',
            'description' => 'Summary of import results, including newly created (inserted), modified existing (updated) and the sum of both (affected).',
            'fields' => [
                'inserted_rows' => new NonNull(Type::int()),
                'inserted_ids' => new NonNull(new ListOfType(Type::string())),
                'updated_rows' => new NonNull(Type::int()),
                'updated_ids' => new NonNull(new ListOfType(Type::string())),
                'affected_rows' => new NonNull(Type::int()),
                'affected_ids' => new NonNull(new ListOfType(Type::string())),
                'failed_rows' => new NonNull(Type::int()),
                'failed_row_numbers' => new NonNull(new ListOfType(Type::int())),
                'errors' => Type::listOf(Type::nonNull($typeLoader->load('_ImportError')))
            ],
        ];
        parent::__construct($config);
    }

}