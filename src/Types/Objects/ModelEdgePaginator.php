<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class ModelEdgePaginator extends ObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'Edges';
    }

    public function __construct(ModelEdge $wrappedType)
    {
        $name = ModelEdge::getStrippedName($wrappedType->name);
        parent::__construct([
            'name' => static::getFullName($name),
            'description' => 'A paginated list of ' . $name . ' relations.',
            'fields' => fn() => [
                'paginatorInfo' => Type::paginatorInfo(),
                'edges' => Type::listOf(Type::nonNull($wrappedType)),
            ],
        ]);
    }

}
