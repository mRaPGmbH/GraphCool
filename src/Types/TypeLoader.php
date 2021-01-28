<?php

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\Type;

class TypeLoader
{
    protected array $types = [];
    protected static array $registry = [];

    public function __construct()
    {
        self::register('paginatorInfo', PaginatorInfoType::class);
        self::register('SQLOperator', SQLOperatorType::class);

    }

    public function load(string $name, ?ModelType $subType = null): callable
    {
        return function() use ($name, $subType) {
            if (!isset($this->types[$name])) {
                $this->types[$name] = $this->create($name, $subType);
            }
            return $this->types[$name];
        };
    }

    public static function register($name, $classname): void
    {
        static::$registry[$name] = $classname;
    }


    protected function create(string $name, ?ModelType $subType = null): Type
    {
        if (strpos($name, 'Paginator') > 0) {
            return new PaginatorType($name, $this, $subType);
        }
        if (isset(static::$registry[$name])) {
            $classname = static::$registry[$name];
            return new $classname();
        }
        return new ModelType($name, $this);
    }

}