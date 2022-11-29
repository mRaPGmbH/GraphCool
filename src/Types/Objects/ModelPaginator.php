<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;

class ModelPaginator extends ObjectType
{

    public function __construct(string $wrappedType)
    {
        parent::__construct([
            'name' => '_' . $wrappedType . 'Paginator',
            'description' => 'A paginated list of ' . $wrappedType . ' items.',
            'fields' => fn() => $this->fieldConfig($wrappedType),
        ]);
    }

    protected function fieldConfig(string $typeName): array
    {
        if (str_ends_with($typeName, '_Job')) {
            $typeName = '_'. substr($typeName, 0, -4) . 'Job';
        }
        if ($typeName === 'History_') {
            $typeName = '_History';
        }
        return [
            'paginatorInfo' => Type::get('_PaginatorInfo'),
            'data' => Type::listOf(Type::nonNull(Type::get($typeName)))
        ];
    }
}
