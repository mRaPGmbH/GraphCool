<?php


namespace Mrap\GraphCool\DataSource;


use Mrap\GraphCool\DataSource\Providers\MysqlDataProvider;
use stdClass;

class DB
{

    /** @var DataProvider */
    protected static DataProvider $provider;

    protected static function get(): DataProvider
    {
        if (!isset(static::$provider)) {
            //$classname = Helper::config('dataProvider');
            //if (!class_exists($classname)) {
                $classname = MysqlDataProvider::class;
            //}
            static::$provider = new $classname();
        }
        return static::$provider;
    }


    public static function load(string $name, string $id): ?stdClass
    {
        return static::get()->load($name, $id);
    }

    public static function findAll(string $name, array $args): stdClass
    {
        return self::get()->findAll($name, $args);
    }

    public static function insert(string $modelName, array $data): stdClass
    {
        return static::get()->insert($modelName, $data);
    }

    public static function update(string $modelName, array $data): stdClass
    {
        return static::get()->update($modelName, $data);
    }

    public static function delete(string $modelName, string $id): stdClass
    {
        return static::get()->delete($modelName, $id);
    }




}