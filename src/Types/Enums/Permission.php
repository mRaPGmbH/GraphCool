<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class Permission extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_Permission';
    }

    public function __construct()
    {
        $config = [
            'name' => static::getFullName(),
            'description' => 'Endpoint operation permissions.',
            'values' => [
                'READ' => ['value' => 'READ', 'description' => 'Load a single entity.'],
                'FIND' => ['value' => 'FIND', 'description' => 'Get a list of entities.'],
                'EXPORT' => ['value' => 'EXPORT', 'description' => 'Download a list of entities in a file.'],

                'CREATE' => ['value' => 'CREATE', 'description' => 'Insert a new entity.'],
                'UPDATE' => ['value' => 'UPDATE', 'description' => 'Modify an existing entity.'],
                'UPDATE_MANY' => ['value' => 'UPDATE_MANY', 'description' => 'Modify a list of existing entities at once.'],

                'DELETE' => ['value' => 'DELETE', 'description' => 'Soft-delete an entity.'],
                'RESTORE' => ['value' => 'RESTORE', 'description' => 'Undelete a previously soft-deleted entity.'],
                'IMPORT' => ['value' => 'IMPORT', 'description' => 'Upload a file of entities to be created and/or updated.'],
            ]
        ];
        parent::__construct($config);
    }
}
