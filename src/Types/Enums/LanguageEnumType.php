<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;

class LanguageEnumType extends EnumType
{

    public function __construct()
    {
        $config = [
            'name' => '_LanguageCode',
            'description' => 'Languages TODO',
            'values' => [
                'de' => ['value' => 'de', 'description' => 'German'],
                'en' => ['value' => 'en', 'description' => 'English']
            ]
        ];
        parent::__construct($config);
    }
}