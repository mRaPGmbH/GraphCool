<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class ModelColumnMapping extends InputObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'ColumnMapping';
    }

    public function __construct(string $model)
    {
        parent::__construct([
            'name' => static::getFullName($model),
            'fields' => fn() => [
                'column' => Type::nonNull(Type::column($model)),
                'label' => Type::string(),
            ],
        ]);
    }

}
