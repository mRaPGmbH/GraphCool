<?php

namespace App\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Types\Type;

class DummyQuery extends Query
{

    public function __construct(?string $model = null)
    {
        $this->name = 'DummyQuery';
        $this->config = [
            'type' => Type::string(),
            'args' => [
                'arg' => Type::mixed()
            ]
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        return $args['arg'] ?? 'dummy-query-resolve';
    }
}