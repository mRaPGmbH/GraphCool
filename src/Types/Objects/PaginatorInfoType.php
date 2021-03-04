<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;


use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PaginatorInfoType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name'   => '_PaginatorInfo',
            'description' => 'Pagination information about the corresponding list of items.',
            'fields' => [
                'count' => new NonNull(Type::int()),
                'currentPage' => new NonNull(Type::int()),
                'firstItem' => new NonNull(Type::int()),
                'hasMorePages' => new NonNull(Type::boolean()),
                'lastItem' => new NonNull(Type::int()),
                'lastPage' => new NonNull(Type::int()),
                'perPage' => new NonNull(Type::int()),
                'total' => new NonNull(Type::int())
            ],
        ];
        parent::__construct($config);
    }

}