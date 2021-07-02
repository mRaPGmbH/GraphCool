<?php

namespace App\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Model\Query;
use Mrap\GraphCool\Types\TypeLoader;

class PublicQuery extends Query
{

    public function __construct(TypeLoader $typeLoader)
    {
        $this->noAuthentication();
        $this->name = 'DummyQuery';
        $this->config = [];
    }

    public function resolve(array $rootValue, array $args, $context, ResolveInfo $info)
    {
        return 'dummy-query-resolve';
    }
}