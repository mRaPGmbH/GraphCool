<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;
use stdClass;

class PaginatorInfoType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => '_PaginatorInfo',
            'description' => 'Pagination information about the corresponding list of items.',
            'fields' => fn() => [
                'count' => Type::nonNull(Type::int()),
                'currentPage' => Type::nonNull(Type::int()),
                'firstItem' => Type::nonNull(Type::int()),
                'hasMorePages' => Type::nonNull(Type::boolean()),
                'lastItem' => Type::nonNull(Type::int()),
                'lastPage' => Type::nonNull(Type::int()),
                'perPage' => Type::nonNull(Type::int()),
                'total' => Type::nonNull(Type::int())
            ],
        ]);
    }

    public static function create(int $count, int $page, int $limit, int $total): stdClass
    {
        $paginatorInfo = new stdClass();
        $paginatorInfo->count = $count;
        $paginatorInfo->currentPage = $page;
        if ($total === 0) {
            $paginatorInfo->firstItem = 0;
        } else {
            $paginatorInfo->firstItem = 1;
        }
        $paginatorInfo->hasMorePages = $total > $page * $limit;
        $paginatorInfo->lastItem = $total;
        $paginatorInfo->lastPage = (int)ceil($total / $limit);
        if ($paginatorInfo->lastPage < 1) {
            $paginatorInfo->lastPage = 1;
        }
        $paginatorInfo->perPage = $limit;
        $paginatorInfo->total = $total;
        return $paginatorInfo;
    }

}