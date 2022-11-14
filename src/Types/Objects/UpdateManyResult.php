<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class UpdateManyResult extends ObjectType
{

    public function __construct()
    {
        parent::__construct([
            'name' => '_UpdateManyResult',
            'description' => 'Result of an updateMany request.',
            'fields' => fn() => [
                'updated_rows' => new NonNull(Type::int()),
            ],
        ]);
    }

}
