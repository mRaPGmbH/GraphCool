<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;


use GraphQL\Type\Definition\EnumType;

class LocaleEnumType extends EnumType
{

    public function __construct()
    {
        $config = [
            'name' => '_LocaleCode',
            'description' => 'Locales TODO',
            'values' => [
                'de_AT' => ['value' => 'de', 'description' => 'German (Austria)'],
                'de_DE' => ['value' => 'de', 'description' => 'German (Germany)'],
                'en_US' => ['value' => 'en', 'description' => 'English (American)'],
                'en_GB' => ['value' => 'en', 'description' => 'English (British)'],
            ]
        ];
        parent::__construct($config);
    }
}