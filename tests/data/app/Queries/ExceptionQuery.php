<?php

namespace App\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Definition\Query;
use RuntimeException;

class ExceptionQuery extends Query
{

    public function __construct(?string $model = null)
    {
        $this->name = 'ExceptionQuery';
        $this->config = [
            'type' => Type::string()
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        throw new RuntimeException('nope');
    }
}