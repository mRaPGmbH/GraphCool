<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class ImportPreview extends ObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'ImportPreview';
    }
    public function __construct(string $model)
    {
        parent::__construct([
            'name' => static::getFullName($model),
            'description' => 'A preview of a ' . $model . ' import.',
            'fields' => fn() => [
                'data' => Type::listOf(Type::nonNull(Type::model($model))),
                'errors' => Type::listOf(Type::nonNull(Type::importError())),
            ],
        ]);
    }
}
