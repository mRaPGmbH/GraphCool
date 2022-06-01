<?php

namespace App\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Types\TypeLoader;
use RuntimeException;

class ExceptionQuery extends Query
{

    public function __construct(TypeLoader $typeLoader)
    {
        $this->name = 'ExceptionQuery';
        $this->config = [
            'type' => Type::string()
        ];
    }

    public function authorize(): void
    {
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        throw new RuntimeException('nope');
    }
}