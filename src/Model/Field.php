<?php


namespace Mrap\GraphCool\Model;

use GraphQL\Type\Definition\Type;

class Field
{
    public const UPDATED_AT = 'UPDATED_AT';
    public const CREATED_AT = 'CREATED_AT';


    public string $type;
    public int $length;
    public bool $null = false;
    public string $description;
    public string $default;
    public bool $readonly = false;

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
                return $value;
        }
    }

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
                return (string) $value;

            case Type::BOOLEAN:
            case Type::INT:
                return (int) $value;

            case Type::FLOAT:
                throw new \Exception('TODO');
        }
    }


    /*
    public function _toSql(string $name): string
    {
        return '`' . $name . '` ' . $this->getTypeForSql();
    }

    protected function getTypeForSql(): string
    {
        if ($this->null === true) {
            $null = 'NULL';
        } else {
            $null = 'NOT NULL';
        }
        if (!isset($this->default)) {
            $default = '';
        } else {
            $default = ' DEFAULT \'' . str_replace('\'', '\\\'', $this->default) . '\'';
        }
        switch ($this->type) {
            case Type::STRING:
                if (isset($this->length) && 0 < $this->length && $this->length < 256) {
                    return 'varchar(' . $this->length . ') ' . $null . $default;
                }
                return 'longtext ' . $null . $default;
            case Type::BOOLEAN:
                return 'tinyint(4) ' . $null. $default;
            case Type::FLOAT:
                return 'double ' . $null . $default;
            case Type::ID:
                return 'int(11) ' . $null . ' auto_increment';
            case Type::INT:
                return 'int(11) ' . $null . $default;
            case static::CREATED_AT:
                return 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP';
            case static::UPDATED_AT:
                return 'timestamp NULL on update CURRENT_TIMESTAMP';
        }
    }*/




}