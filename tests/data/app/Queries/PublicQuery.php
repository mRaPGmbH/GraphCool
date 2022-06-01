<?php

namespace App\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Types\TypeLoader;

class PublicQuery extends Query
{

    public function __construct(TypeLoader $typeLoader)
    {
        $this->name = 'PublicQuery';
        $this->config = [];
    }

    public function authorize(): void
    {
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        return 'dummy-query-resolve';
    }
}