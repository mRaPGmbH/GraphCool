<?php

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Enums\DynamicEnumType;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\LanguageEnumType;
use Mrap\GraphCool\Types\Enums\LocaleEnumType;

class TypeLoader
{
    protected array $types = [];
    protected static array $registry = [];

    public function __construct()
    {
        self::register('_PaginatorInfo', PaginatorInfoType::class);
        self::register('_SQLOperator', SQLOperatorType::class);
        self::register('_CountryCode', CountryCodeEnumType::class);
        self::register('_LanguageCode', LanguageEnumType::class);
        self::register('_CurrencyCode', CurrencyEnumType::class);
        self::register('_LocaleCode', LocaleEnumType::class);
    }

    public function load(string $name, ?ModelType $subType = null, ?ModelType $parentType = null): callable
    {
        return function() use ($parentType, $name, $subType) {
            if (!isset($this->types[$name])) {
                $this->types[$name] = $this->create($name, $subType, $parentType);
            }
            return $this->types[$name];
        };
    }

    public function loadForField(Field $field, string $name = null): Type
    {
        return match ($field->type) {
            default => Type::string(),
            Type::BOOLEAN => Type::boolean(),
            Type::FLOAT, Field::DECIMAL => Type::float(),
            Type::ID => Type::id(),
            Type::INT => Type::int(),
            Field::COUNTRY_CODE => $this->load('_CountryCode')(),
            Field::CURRENCY_CODE => $this->load('_CurrencyCode')(),
            Field::LANGUAGE_CODE => $this->load('_LanguageCode')(),
            Field::LOCALE_CODE => $this->load('_LocaleCode')(),
            Field::ENUM => $this->loadEnumType($name, $field),
        };
    }

    protected function loadEnumType(string $name, Field $field): DynamicEnumType
    {
        $key = '_' . $name . 'Enum';
        if (!isset($this->types[$key])) {
            $this->types[$key] = new DynamicEnumType($key, $field);
        }
        return $this->types[$key];
    }

    public static function register($name, $classname): void
    {
        static::$registry[$name] = $classname;
    }


    protected function create(string $name, ?ModelType $subType = null, ?ModelType $parentType = null): Type
    {
        if (isset(static::$registry[$name])) {
            $classname = static::$registry[$name];
            return new $classname();
        }
        if ($name[0] === '_') {
            return $this->createSpecial(substr($name, 1), $subType, $parentType);
        }
        return new ModelType($name, $this);
    }

    protected function createSpecial(string $name, ?ModelType $subType = null, ?ModelType $parentType = null)
    {
        if (substr($name, -9) === 'Paginator') {
            if ($subType === null) {
                $subType = $this->load(substr($name, 0, -9))();
            }
            return new PaginatorType($subType, $this);
        }
        if (substr($name, -5) === 'Edges') {
            $names = explode('_', substr($name, 0, -5));
            $key = $names[1];
            if ($parentType === null) {
                $parentType = $this->load($names[0])();
            }
            return new EdgesType($key, $parentType, $this);
        }
        if (substr($name, -4) === 'Edge') {
            $names = explode('_', substr($name, 0, -4));
            $key = $names[1];
            if ($parentType === null) {
                $parentType = $this->load($names[0])();
            }
            return new EdgeType($key, $parentType, $this);
        }
        if (substr($name, -15) === 'WhereConditions') {
            if ($subType === null) {
                $subType = $this->load(substr($name, 0, -15))();
            }
            return new WhereInputType($subType, $this);
        }
        if (substr($name, -8) === 'Relation') {
            $names = explode('_', substr($name, 0, -8));
            $key = $names[1];
            if ($parentType === null) {
                $parentType = $this->load($names[0])();
            }
            return new EdgeInputType($key, $parentType, $this);
        }

        if (substr($name, -4) === 'Enum') {
            throw new \Exception('TODO!');
        }

        throw new \Exception('unhandled createSpecial: '.$name);

    }


}