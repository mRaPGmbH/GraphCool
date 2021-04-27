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


    public static function load(?string $tenantId, string $name, string $id): ?stdClass
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->load($tenantId, $name, $id);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function getMax(?string $tenantId, string $name, string $key): float|bool|int|string
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->getMax($tenantId, $name, $key);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function findAll(?string $tenantId, string $name, array $args): stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  self::get()->findAll($tenantId, $name, $args);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function insert(string $tenantId, string $modelName, array $data): stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  static::get()->insert($tenantId, $modelName, $data);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function update(string $tenantId, string $modelName, array $data): stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  static::get()->update($tenantId, $modelName, $data);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function updateAll(string $tenantId, string $modelName, array $data): stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  static::get()->updateMany($tenantId, $modelName, $data);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function delete(string $tenantId, string $modelName, string $id): ?stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  static::get()->delete($tenantId, $modelName, $id);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function restore(string $tenantId, string $modelName, string $id): stdClass
    {
        StopWatch::start(__METHOD__);
        $result =  static::get()->restore($tenantId, $modelName, $id);
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