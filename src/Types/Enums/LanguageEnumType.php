<?php


namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;

class LanguageEnumType extends EnumType
{

    public function __construct()
    {
        $config = [
            'name' => '_LanguageEnum',
            'description' => 'Languages TODO',
            'values' => [
                'de' => ['value' => 'de', 'description' => 'German'],
                'en' => ['value' => 'en', 'description' => 'English']
            ]
        ];
        parent::__construct($config);
    }
}