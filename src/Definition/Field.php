<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

use GraphQL\Type\Definition\Type;

class Field
{
    public const DELETED_AT = 'DELETED_AT';
    public const UPDATED_AT = 'UPDATED_AT';
    public const CREATED_AT = 'CREATED_AT';
    public const COUNTRY_CODE = 'COUNTRY_CODE';
    public const CURRENCY_CODE = 'CURRENCY_CODE';
    public const LANGUAGE_CODE = 'LANGUAGE_CODE';
    public const LOCALE_CODE = 'LOCALE_CODE';
    public const ENUM = 'ENUM';
    public const DECIMAL = 'DECIMAL';
    public const DATE_TIME = 'DATE_TIME';
    public const DATE = 'DATE';
    public const TIME = 'TIME';
    public const TIMEZONE_OFFSET = 'TIMEZONE_OFFSET';
    public const FILE = 'FILE';

    public string $type;
    public int $decimalPlaces;
    public bool $null = false;
    public string $description;
    public string|int|float|bool $default;
    public bool $readonly = false;
    /** @var mixed[] */
    public array $enumValues;
    public bool $unique = false;
    public bool $uniqueIgnoreTrashed = false;

    protected function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function string(): Field
    {
        return new Field(Type::STRING);
    }

    public static function id(): Field
    {
        return (new Field(Type::ID))->readonly();
    }

    public function readonly(): Field
    {
        $this->readonly = true;
        return $this;
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

    public static function file(): Field
    {
        return (new Field(static::FILE));
    }

    public static function decimal(int $decimalPlaces = 2): Field
    {
        $field = new Field(static::DECIMAL);
        $field->decimalPlaces = $decimalPlaces;
        return $field;
    }

    public static function createdAt(): Field
    {
        return (new Field(static::CREATED_AT))->readonly();
    }

    public static function updatedAt(): Field
    {
        return (new Field(static::UPDATED_AT))->nullable()->readonly();
    }


    public function nullable(): Field
    {
        $this->null = true;
        return $this;
    }

    public static function deletedAt(): Field
    {
        return (new Field(static::DELETED_AT))->nullable()->readonly();
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

    public static function date(): Field
    {
        return new Field(static::DATE);
    }

    public static function dateTime(): Field
    {
        return new Field(static::DATE_TIME);
    }

    public static function time(): Field
    {
        return new Field(static::TIME);
    }

    public static function timezoneOffset(): Field
    {
        return new Field(static::TIMEZONE_OFFSET);
    }

    /**
     * @param mixed[] $values
     * @return Field
     */
    public static function enum(array $values): Field
    {
        $field = new Field(static::ENUM);
        $field->enumValues = $values;
        return $field;
    }

    public function unique(bool $ignoreTrashed = false): Field
    {
        $this->unique = true;
        $this->uniqueIgnoreTrashed = $ignoreTrashed;
        return $this;
    }

    public function description(string $description): Field
    {
        $this->description = $description;
        return $this;
    }

    public function default(string|int|float|bool $default): Field
    {
        $this->default = $default;
        return $this;
    }

}