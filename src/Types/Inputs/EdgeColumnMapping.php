<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class EdgeColumnMapping extends InputObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'EdgeColumnMapping';
    }

    public function __construct(Relation $relation)
    {
        parent::__construct([
            'name' => static::getFullName($relation->namekey),
            'fields' => fn() => [
                'column' => Type::nonNull(Type::column($relation)),
                'label' => Type::string(),
            ],
        ]);
    }

}
