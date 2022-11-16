<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;

class ImportPreviewType extends ObjectType
{
    public function __construct(string $name)
    {
        $typeName = substr($name, 1, -13);
        parent::__construct([
            'name' => $name,
            'description' => 'A preview of a ' . $typeName . ' import.',
            'fields' => fn() => [
                'data' => Type::listOf(Type::nonNull(Type::get($typeName))),
                'errors' => Type::listOf(Type::nonNull(Type::get('_ImportError')))
            ],
        ]);
    }
}
