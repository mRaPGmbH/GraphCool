<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Types\TypeLoader;

class ImportPreviewType extends ObjectType
{
    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $typeName = substr($name, 1, -13);
        $config = [
            'name' => $name,
            'description' => 'A preview of a ' . $typeName . ' import.',
            'fields' => [
                'data' => new ListOfType(new NonNull($typeLoader->load($typeName))),
                'errors' => Type::listOf(Type::nonNull($typeLoader->load('_ImportError')))
            ],
        ];
        parent::__construct($config);
    }
}