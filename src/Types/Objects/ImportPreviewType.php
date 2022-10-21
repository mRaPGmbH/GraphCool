<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;
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
                'data' => Type::listOf(Type::nonNull(Type::get($typeName))),
                'errors' => Type::listOf(Type::nonNull(Type::get('_ImportError')))
            ],
        ];
        parent::__construct($config);
    }
}