<?php

namespace App\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\Query;

class PublicQuery extends Query
{

    public function __construct(?string $model = null)
    {
        $this->name = 'PublicQuery';
        $this->config = [];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        return 'dummy-query-resolve';
    }
}