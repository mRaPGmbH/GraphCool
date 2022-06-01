<?php

namespace App\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Types\TypeLoader;

class DummyQuery extends Query
{

    public function __construct(TypeLoader $typeLoader)
    {
        $this->name = 'DummyQuery';
        $this->config = [
            'type' => Type::string(),
            'args' => [
                'arg' => $typeLoader->load('Mixed')
            ]
        ];
    }

    public function authorize(): void
    {
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        return $args['arg'] ?? 'dummy-query-resolve';
    }
}