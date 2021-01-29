<?php


namespace Mrap\GraphCool\Model;

use GraphQL\Type\Definition\Type;

class Field
{
    public const UPDATED_AT = 'UPDATED_AT';
    public const CREATED_AT = 'CREATED_AT';
    public const COUNTRY_CODE = 'COUNTRY_CODE';
    public const CURRENCY_CODE = 'CURRENCY_CODE';
    public const LANGUAGE_CODE = 'LANGUAGE_CODE';
    public const LOCALE_CODE = 'LOCALE_CODE';
    public const ENUM = 'ENUM';


    public string $type;
    public int $length;
    public bool $null = false;
    public string $description;
    public string $default;
    public bool $readonly = false;
    public array $enumValues;

    protected function __construct(string $type, int $length = null)
    {
        $this->type = $type;
        if ($length !== null) {
            $this->length = $length;
        }
    }

    public static function string(int $length = null): Field
    {
        return new Field(Type::STRING, $length);
    }

    public static function id(): Field
    {
        return (new Field(Type::ID))->readonly();
    }

    public static function bool(): Field
    {
        return new Field(Type::BOOLEAN);
    }

    public static function int(): Field
    {
        return new Field(Type::INT);
    }

    public static function float(): Field
    {
        return new Field(Type::FLOAT);
    }

    public static function createdAt(): Field
    {
        return (new Field(static::CREATED_AT))->readonly();
    }

    public static function updatedAt(): Field
    {
        return (new Field(static::UPDATED_AT))->nullable()->readonly();
    }

    public static function countryCode(): Field
    {
        return new Field(static::COUNTRY_CODE);
    }

    public static function languageCode(): Field
    {
        return new Field(static::LANGUAGE_CODE);
    }

    public static function currencyCode(): Field
    {
        return new Field(static::CURRENCY_CODE);
    }

    public static function localeCode(): Field
    {
        return new Field(static::LOCALE_CODE);
    }

    public static function enum(array $values): Field
    {
        $field = new Field(static::ENUM);
        $field->enumValues = $values;
        return $field;
    }

    public function nullable(): Field
    {
        $this->null = true;
        return $this;
    }

    public function description(string $description): Field
    {
        $this->description = $description;
        return $this;
    }

    public function default(string $default): Field
    {
        $this->default = $default;
        return $this;
    }

    public function readonly(): Field
    {
        $this->readonly = true;
        return $this;
    }

    // TODO: move elsewhere
    public function convert($value)
    {
        if ($value === null && $this->null === true) {
            return null;
        }
        switch ($this->type) {
            case Type::ID:
            case Type::STRING:
                return (string) $value;
            case Type::BOOLEAN:
                return (bool) $value;
            case Type::FLOAT:
                return (double) $value;
            case Type::INT:
                return (int) $value;
            case static::CREATED_AT:
            case static::UPDATED_AT:
            case static::LOCALE_CODE:
            case static::CURRENCY_CODE:
            case static::COUNTRY_CODE:
            case static::LANGUAGE_CODE:
            case static::ENUM:
                return $value;
        }
    }

    // TODO: move elsewhere
    public function convertBack($value)
    {
        if ($value === null && $this->null === true) {
            return null;
        }
        switch ($this->type) {
            case Type::ID:
            case Type::STRING:
            case static::CREATED_AT:
            case static::UPDATED_AT:
            case static::LOCALE_CODE:
            case static::CURRENCY_CODE:
            case static::COUNTRY_CODE:
            case static::LANGUAGE_CODE:
            case static::ENUM:
                return (string) $value;

            case Type::BOOLEAN:
            case Type::INT:
                return (int) $value;

            case Type::FLOAT:
                throw new \Exception('TODO');
        }
    }




}