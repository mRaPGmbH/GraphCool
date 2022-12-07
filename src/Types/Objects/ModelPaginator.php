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

    protected function fieldConfig(string $wrappedType): array
    {
        if (str_ends_with($wrappedType, '_Job')) {
            $wrappedType = '_'. substr($wrappedType, 0, -4) . 'Job';
        }
        if ($wrappedType === 'History_') {
            $wrappedType = '_History';
        }
        return [
            'paginatorInfo' => Type::paginatorInfo(),
            'data' => Type::listOf(Type::nonNull(Type::get($wrappedType)))
        ];
    }
}
