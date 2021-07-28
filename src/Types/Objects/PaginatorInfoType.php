<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;


use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use stdClass;

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
        $paginatorInfo->lastPage = ceil($total / $limit);
        if ($paginatorInfo->lastPage < 1) {
            $paginatorInfo->lastPage = 1;
        }
        $paginatorInfo->perPage = $limit;
        $paginatorInfo->total = $total;
        return $paginatorInfo;
    }

}