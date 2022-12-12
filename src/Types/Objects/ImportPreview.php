<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;

class ImportPreview extends ObjectType
{
    public function __construct(string $model)
    {
        parent::__construct([
            'name' => '_' . $model . 'ImportPreview',
            'description' => 'A preview of a ' . $model . ' import.',
            'fields' => fn() => [
                'data' => Type::listOf(Type::nonNull(Type::model($model))),
                'errors' => Type::listOf(Type::nonNull(Type::get('_ImportError')))
            ],
        ]);
    }
}
