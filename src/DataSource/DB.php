<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Definition\Job;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\Result;
use Mrap\GraphCool\Utils\StopWatch;
use Ramsey\Uuid\Uuid;
use stdClass;

class DB
{

    /** @var DataProvider */
    protected static DataProvider $provider;

    public static function setProvider(DataProvider $provider): void
    {
        static::$provider = $provider;
    }

    public static function load(?string $tenantId, string $name, string $id, ?string $resultType = Result::DEFAULT): ?stdClass
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->load($tenantId, $id, $resultType);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    protected static function get(): DataProvider
    {
        if (!isset(static::$provider)) {
            // @codeCoverageIgnoreStart
            //$classname = Helper::config('dataProvider');
            //if (!class_exists($classname)) {
            $classname = MysqlDataProvider::class;
            //}
            static::$provider = new $classname();
            // @codeCoverageIgnoreEnd
        }
        return static::$provider;
    }

    public static function getMax(?string $tenantId, string $name, string $key): float|bool|int|string
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->getMax($tenantId, $name, $key);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function getSum(?string $tenantId, string $name, string $key): float|int
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->getSum($tenantId, $name, $key);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function getCount(?string $tenantId, string $name): int
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->getCount($tenantId, $name);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    /**
     * @param string|null $tenantId
     * @param string $name
     * @param mixed[] $args
     * @return stdClass
     */
    public static function findNodes(?string $tenantId, string $name, array $args): stdClass
    {
        StopWatch::start(__METHOD__);
        $result = self::get()->findNodes($tenantId, $name, $args);
        StopWatch::stop(__METHOD__);
        return $result;
    }


    /**
     * @param string|null $tenantId
     * @param string $name
     * @param mixed[] $args
     * @return stdClass
     */
    public static function loadNodes(?string $tenantId, array $ids, ?string $resultType = Result::DEFAULT): array
    {
        StopWatch::start(__METHOD__);
        $result = self::get()->loadNodes($tenantId, $ids, $resultType);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function findEdges(?string $tenantId, string $nodeId, Relation $relation, array $args): array|stdClass
    {
        StopWatch::start(__METHOD__);
        $result = self::get()->findEdges($tenantId, $nodeId, $relation, $args);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function loadEdges(array $ids): array
    {
        StopWatch::start(__METHOD__);
        $result = self::get()->loadEdges($ids);
        StopWatch::stop(__METHOD__);
        return $result;
    }



    /**
     * @param string $tenantId
     * @param string $modelName
     * @param mixed[] $data
     * @return stdClass
     */
    public static function insert(string $tenantId, string $modelName, array $data): ?stdClass
    {
        StopWatch::start(__METHOD__);
        //$id = static::id();
        //$model = Model::get($modelName);
        //$data = $model->prepare($id, $data);

        $result = static::get()->insert($tenantId, $modelName, $data);
        FullTextIndex::index($tenantId, $modelName, $result->id);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function id(): string
    {
        /*
        $nodeProvider = new RandomNodeProvider();
        return Uuid::uuid1($nodeProvider->getNode())->toString();
        */
        return Uuid::uuid4()->toString();
    }

    /**
     * @param string $tenantId
     * @param string $modelName
     * @param mixed[] $data
     * @return stdClass|null
     */
    public static function update(string $tenantId, string $modelName, array $data): ?stdClass
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->update($tenantId, $modelName, $data);
        FullTextIndex::index($tenantId, $modelName, $result->id);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    /**
     * @param string $tenantId
     * @param string $modelName
     * @param mixed[] $data
     * @return stdClass
     */
    public static function updateAll(string $tenantId, string $modelName, array $data): stdClass
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->updateMany($tenantId, $modelName, $data);
        foreach ($result->ids as $id) {
            FullTextIndex::index($tenantId, $modelName, $id);
        }
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function delete(string $tenantId, string $modelName, string $id): ?stdClass
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->delete($tenantId, $modelName, $id);
        FullTextIndex::delete($tenantId, $modelName, $id);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function deleteMany(string $tenantId, string $modelName, array $ids): bool
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->deleteMany($tenantId, $modelName, $ids);
        if ($result->success) {
            foreach ($result->ids as $id) {
                FullTextIndex::delete($tenantId, $modelName, $id);
            }
        }
        StopWatch::stop(__METHOD__);
        return $result->success;
    }

    public static function restore(string $tenantId, string $modelName, string $id): stdClass
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->restore($tenantId, $modelName, $id);
        FullTextIndex::index($tenantId, $modelName, $id);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function migrate(): void
    {
        StopWatch::start(__METHOD__);
        static::get()->migrate();
        StopWatch::stop(__METHOD__);
    }

    public static function increment(string $tenantId, string $key, int $min = 0, bool $transaction = true): int
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->increment($tenantId, $key, $min, $transaction);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function addJob(string $tenantId, string $worker, ?string $model, ?array $data = null): string
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->addJob($tenantId, $worker, $model, $data);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function takeJob(): ?Job
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->takeJob();
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function finishJob(string $id, ?array $result = null, bool $failed = false): void
    {
        StopWatch::start(__METHOD__);
        static::get()->finishJob($id, $result, $failed);
        StopWatch::stop(__METHOD__);
    }

    public static function getJob(string $tenantId, string $name, string $id): ?stdClass
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->getJob($tenantId, $name, $id);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function findJobs(?string $tenantId, string $name, array $args): stdClass
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->findJobs($tenantId, $name, $args);
        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function findHistory(?string $tenantId, array $args): stdClass
    {
        StopWatch::start(__METHOD__);
        $result = static::get()->findHistory($tenantId, $args);
        StopWatch::stop(__METHOD__);
        return $result;
    }

}
