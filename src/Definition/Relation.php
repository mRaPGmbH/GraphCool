<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

use stdClass;

class Relation extends stdClass
{
    public const BELONGS_TO = 'BELONGS_TO';
    public const BELONGS_TO_MANY = 'BELONGS_TO_MANY';
    public const HAS_MANY = 'HAS_MANY';
    public const HAS_ONE = 'HAS_ONE';

    public string $type;
    public string $classname;
    public string $name;
    public string $namekey;
    public bool $null = false;
    public bool $history = false;

    protected function __construct(string $type, string $classname)
    {
        $this->type = $type;
        $this->classname = $classname;
        if (strpos($classname, '\\') === false) {
            $this->name = $classname;
        } else {
            $this->name = substr($classname, strrpos($classname, '\\') + 1);
        }
        $this->created_at = Field::createdAt();
        $this->updated_at = Field::updatedAt();
        $this->deleted_at = Field::deletedAt();
    }

    public static function belongsTo(string $classname): Relation
    {
        return new Relation(static::BELONGS_TO, $classname);
    }

    public static function belongsToMany(string $classname): Relation
    {
        return new Relation(static::BELONGS_TO_MANY, $classname);
    }

    public static function hasMany(string $classname): Relation
    {
        return new Relation(static::HAS_MANY, $classname);
    }

    public static function hasOne(string $classname): Relation
    {
        return new Relation(static::HAS_ONE, $classname);
    }

    public function nullable(): Relation
    {
        $this->null = true;
        return $this;
    }

    public function history(): Relation
    {
        $this->history = true;
        return $this;
    }

}
