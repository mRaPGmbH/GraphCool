<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;

class ModelEdgePaginator extends ObjectType
{
    public function __construct(ModelEdge $wrappedType)
    {
        parent::__construct([
            'name' => $wrappedType->name . 's',
            'description' => 'A paginated list of ' . substr($wrappedType->name, 1, -4) . ' relations.',
            'fields' => fn() => [
                'paginatorInfo' => Type::paginatorInfo(),
                'edges' => Type::listOf(Type::nonNull($wrappedType)),
            ],
        ]);
    }

}
