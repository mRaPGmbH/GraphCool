<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class ModelColumnMapping extends InputObjectType
{

    public function __construct(string $model)
    {
        parent::__construct([
            'name' => '_' . $model . 'ColumnMapping',
            'fields' => fn() => [
                'column' => Type::nonNull(Type::column($model)),
                'label' => Type::string(),
            ],
        ]);
    }

}
