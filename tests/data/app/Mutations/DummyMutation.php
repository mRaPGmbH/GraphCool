<?php

namespace App\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Types\TypeLoader;

class DummyMutation extends Mutation
{

    public function __construct(?string $model = null)
    {
        $this->name = 'DummyMutation';
        $this->config = [];
    }

    public function resolve(array $rootValue, array $args, $context, ResolveInfo $info)
    {
        return 'dummy-mutation-resolve';
    }
}