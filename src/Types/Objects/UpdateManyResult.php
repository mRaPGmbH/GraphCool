<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;


use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class UpdateManyResult extends ObjectType
{

    public function __construct()
    {
        $config = [
            'name'   => '_UpdateManyResult',
            'description' => 'Result of an updateMany request.',
            'fields' => [
                'updated_rows' => new NonNull(Type::int()),
            ],
        ];
        parent::__construct($config);
    }

}