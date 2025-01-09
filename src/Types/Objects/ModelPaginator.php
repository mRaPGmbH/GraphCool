<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class ModelPaginator extends ObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'Paginator';
    }

    public function __construct(string $wrappedType)
    {
        parent::__construct([
            'name' => static::getFullName($wrappedType),
            'description' => 'A paginated list of ' . $wrappedType . ' items.',
            'fields' => fn() => $this->fieldConfig($wrappedType),
        ]);
    }

    protected function fieldConfig(string $wrappedType): array
    {
        $type = match($wrappedType) {
            'Import_Job' => Type::job('Import'),
            'Export_Job' => Type::job('Export'),
            'Delete_Job' => Type::job('Delete'),
            'History_' => Type::history(),
            default => Type::model($wrappedType)
        };
        return [
            'paginatorInfo' => Type::paginatorInfo(),
            'data' => Type::listOf(Type::nonNull($type))
        ];
    }
}
