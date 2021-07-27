<?php

namespace App\Queries;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Query;
use Mrap\GraphCool\Types\TypeLoader;

class ExceptionQuery extends Query
{

    public function __construct(TypeLoader $typeLoader)
    {
        $this->name = 'ExceptionQuery';
        $this->config = [
            'type' => Type::string()
        ];
    }

    public function resolve(array $rootValue, array $args, $context, ResolveInfo $info)
    {
        throw new Error('nope');
    }
}