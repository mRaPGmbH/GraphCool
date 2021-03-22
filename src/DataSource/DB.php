<?php
declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;


use Mrap\GraphCool\DataSource\Providers\MysqlDataProvider;
use Mrap\GraphCool\Utils\StopWatch;
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
        StopWatch::start(__METHOD__);
        $result = static::get()->load($name, $id);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function findAll(string $name, array $args): stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  self::get()->findAll($name, $args);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function insert(string $modelName, array $data): stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  static::get()->insert($modelName, $data);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function update(string $modelName, array $data): stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  static::get()->update($modelName, $data);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function delete(string $modelName, string $id): ?stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  static::get()->delete($modelName, $id);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function restore(string $modelName, string $id): stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  static::get()->restore($modelName, $id);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function migrate(): void
    {
        StopWatch::start(__METHOD__);
        static::get()->migrate();
        StopWatch::stop(__METHOD__);
    }



}