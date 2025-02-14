<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use App\Models\Note;
use Carbon\Carbon;
use Closure;
use GraphQL\Error\Error;
use JsonException;
use Mrap\GraphCool\DataSource\DataProvider;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Job;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\Result;
use Mrap\GraphCool\Types\Objects\PaginatorInfo;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\JwtAuthentication;
use stdClass;

use function Mrap\GraphCool\model;

class MysqlDataProvider implements DataProvider
{

    protected int $search = 0;

    /**
     * @codeCoverageIgnore
     */
    public function migrate(): void
    {
        $migration = new MysqlMigration();
        $migration->migrate();
    }

    /**
     * @param string|null $tenantId
     * @param string $name
     * @param mixed[] $args
     * @return stdClass
     * @throws Error
     */
    public function findNodes(?string $tenantId, string $name, array $args): stdClass
    {
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        if ($page < 1) {
            throw new Error('Page cannot be less than 1');
        }
        if ($limit < 1) {
            throw new Error('First cannot be less than 1');
        }
        $offset = ($page - 1) * $limit;
        $resultType = $args['result'] ?? Result::DEFAULT;

        $model = model($name);

        $data = $this->getIdsForWhere($model, $name, $tenantId, $args, $resultType, $limit, $offset);

        $result = new stdClass();
        $result->paginatorInfo = PaginatorInfo::create(count($data->ids), $page, $limit, $data->total);
        $result->data = function() use($tenantId, $data, $resultType) {return $this->loadNodes($tenantId, $data->ids, $resultType);};
        $result->ids = $data->ids;
        return $result;
    }


    public function getMax(?string $tenantId, string $name, string $key): float|bool|int|string
    {
        $model = model($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant($tenantId);

        $valueType = match ($model->$key->type) {
            Type::BOOLEAN, Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET, Field::DECIMAL => 'value_int',
            Type::FLOAT => 'value_float',
            default => 'value_string'
        };

        $query->selectMax($key, 'max', $valueType)
            ->withTrashed();
        $result = Mysql::fetch($query->toSql(), $query->getParameters());
        if ($result === null) {
            return match ($model->$key->type) {
                Type::BOOLEAN => false,
                Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET => 0,
                Type::FLOAT, Field::DECIMAL => 0.0,
                default => ''
            };
        }

        $property = new stdClass();
        $property->$valueType = $result->max;

        return MysqlConverter::convertDatabaseTypeToOutput($model->$key, $property);
    }

    public function getSum(?string $tenantId, string $name, string $key): float|bool|int|string
    {
        $model = model($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant($tenantId);

        $valueType = match ($model->$key->type) {
            Type::BOOLEAN, Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET, Field::DECIMAL => 'value_int',
            Type::FLOAT => 'value_float',
            default => 'value_string'
        };

        $query->selectSum($key, 'sum', $valueType);
        $result = Mysql::fetch($query->toSql(), $query->getParameters());
        if ($result === null) {
            return match ($model->$key->type) {
                Type::BOOLEAN => false,
                Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET => 0,
                Type::FLOAT, Field::DECIMAL => 0.0,
                default => ''
            };
        }

        $property = new stdClass();
        $property->$valueType = $result->sum;

        return MysqlConverter::convertDatabaseTypeToOutput($model->$key, $property);
    }

    public function getCount(?string $tenantId, string $name): int
    {
        $model = model($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant($tenantId);
        return (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());
    }


    /**
     * @param string|null $tenantId
     * @param string[] $ids
     * @param string|null $resultType
     * @return stdClass[]
     */
    public function loadNodes(?string $tenantId, array $ids, ?string $resultType = Result::DEFAULT): array {
        $results = Mysql::nodeReader()->loadMany($tenantId, $ids, $resultType);
        foreach ($results as $key => $result) {
            $results[$key] = $this->retrieveFiles($result);
        }
        return $results;
    }

    public function load(?string $tenantId, string $id, ?string $resultType = Result::DEFAULT): ?stdClass {
        $data = Mysql::nodeReader()->load($tenantId, $id, $resultType);
        if ($data !== null) {
            $data = $this->retrieveFiles($data);
        }
        return $data;
    }

    /**
     * @param string $tenantId
     * @param string $name
     * @param mixed[] $data
     * @return stdClass
     * @throws Error
     */
    public function insert(string $tenantId, string $name, array $data): ?stdClass
    {
        Mysql::beginTransaction();
        try {
            $model = model($name);
            $id = DB::id();
            $data = $this->storeFiles($model, $name, $data, $id);
            $data = $model->beforeInsert($tenantId, $data);
            $this->checkUnique($tenantId, $model, $name, $data);
            $data = $this->checkIncrements($tenantId, $model, $name, $data);

            Mysql::nodeWriter()->insert($tenantId, $name, $id, $data);

            $loaded = $this->load($tenantId, $id);
            if ($loaded !== null) {
                $model->afterInsert($loaded);
                Mysql::history()->recordCreate($loaded, $model->getPropertyNamesForHistory($data));
            }
            Mysql::commit();
            return $loaded;
        } catch (\Throwable $e) {
            Mysql::rollBack();
            throw $e;
        }
    }

    /**
     * @param string $tenantId
     * @param string $name
     * @param mixed[] $data
     * @return stdClass|null
     * @throws Error
     */
    public function update(string $tenantId, string $name, array $data): ?stdClass
    {
        //Mysql::beginTransaction();
        try {
            $model = model($name);
            $oldNode = $this->load($tenantId, $data['id'], Result::WITH_TRASHED);
            if ($oldNode === null) {
                throw new Error($name . ' with ID ' . $data['id'] . ' not found.');
            }
            $updates = $data['data'] ?? [];
            $updates = $this->storeFiles($model, $name, $updates, $data['id']);
            $updates = $model->beforeUpdate($tenantId, $data['id'], $updates); // TODO: add $oldNode param?

            $history = $model->getPropertyNamesForHistory($updates);
            foreach ($history as $key => $subProperties) {
                if (!is_array($subProperties)) {
                    continue;
                }
                if (!array_key_exists($key, $updates)) {
                    continue;
                }
                $closure = $oldNode->$key;
                $oldNode->$key = $closure([])['edges'] ?? []; // load all edges before modification
            }

            $this->checkUnique($tenantId, $model, $name, $updates, $data['id']);
            $this->checkNull($model, $updates);

            Mysql::nodeWriter()->update($tenantId, $name, $data['id'], $updates);

            $loaded = $this->load($tenantId, $data['id'], Result::WITH_TRASHED);
            if ($loaded !== null) {
                $model->afterUpdate($loaded);
                Mysql::history()->recordUpdate($oldNode, $loaded, $history);
            }
            //Mysql::commit();
            return $loaded;
        } catch (\Throwable $e) {
            //Mysql::rollBack();
            throw $e;
        }
    }

    /**
     * @param string $tenantId
     * @param string $name
     * @param mixed[] $data
     * @return stdClass
     * @throws Error
     */
    public function updateMany(string $tenantId, string $name, array $data): stdClass
    {
        Mysql::beginTransaction();
        try {
            $model = model($name);
            $updateData = $data['data'] ?? [];
            $resultType = $data['result'] ?? Result::DEFAULT;
            $this->checkNull($model, $updateData);
            $ids = $this->getIdsForWhere($model, $name, $tenantId, $data, $resultType)->ids;
            $result = Mysql::nodeWriter()->updateMany($tenantId, $name, $ids, $updateData);

            $model->afterBulkUpdate($this->getClosure($tenantId, $name, $ids, $resultType));
            Mysql::history()->recordMassUpdate($tenantId, $ids, $name, $updateData);
            $result->ids = $ids;
            Mysql::commit();
            return $result;
        } catch (\Throwable $e) {
            Mysql::rollBack();
            throw $e;
        }
    }

    public function delete(string $tenantId, string $name, string $id): ?stdClass
    {
        Mysql::beginTransaction();
        try {
            $node = $this->load($tenantId, $id, Result::WITH_TRASHED);
            if ($node === null) {
                Mysql::rollBack();
                return null;
            }
            $this->softDeleteFiles($name, $node);
            Mysql::nodeWriter()->delete($tenantId, $id);
            $model = model($name);
            $model->afterDelete($node);
            Mysql::history()->recordDelete($node, $model->getPropertyNamesForHistory());
            Mysql::commit();
            $node->deleted_at = date('Y-m-d H:i:s');
            return $node;
        } catch (\Throwable $e) {
            Mysql::rollBack();
            throw $e;
        }
    }

    public function deleteMany(string $tenantId, string $name, array $data): stdClass
    {
        $model = model($name);
        $ids = $this->getIdsForWhere($model, $name, $tenantId, $data, Result::DEFAULT)->ids;
        $historyNames = $model->getPropertyNamesForHistory();

        foreach (array_chunk($ids, 500) as $batch) {
            if (!$this->deleteBatch($tenantId, $batch, $historyNames, $name, $model)) {
                return (object)[
                    'ids' => $ids,
                    'success' => false,
                ];
            }
        }
        return (object)[
            'ids' => $ids,
            'success' => true,
        ];
    }

    protected function deleteBatch(string $tenantId, array $batch, array $historyNames, string $name, Model $model): bool
    {
        set_time_limit(60);
        Mysql::beginTransaction();
        try {
            Mysql::nodeWriter()->deleteMany($tenantId, $batch);
            $nodes = $this->loadNodes($tenantId, $batch, Result::WITH_TRASHED);
            if (count($nodes) === 0) {
                Mysql::rollBack();
                return false;
            }
            foreach ($nodes as $node) {
                $this->softDeleteFiles($name, $node);
                $model->afterDelete($node);
                $model->onDelete($node);
                Mysql::history()->recordDelete($node, $historyNames);
            }
            Mysql::commit();
        } catch (\Throwable $e) {
            Mysql::rollBack();
            throw $e;
        }
        $model->onShutdown();

        return true;
    }


    public function restore(?string $tenantId, string $name, string $id): stdClass
    {
        Mysql::beginTransaction();
        try {
            $model = model($name);
            $model->beforeRestore($tenantId, $id);
            $node = $this->load($tenantId, $id, Result::WITH_TRASHED);
            if ($node === null) {
                throw new Error($name . ' with ID ' . $id . ' not found.');
            }
            $this->restoreFiles($name, $node);
            Mysql::nodeWriter()->restore($tenantId, $id);
            $node->deleted_at = null;
            $model->afterRestore($node);
            Mysql::history()->recordRestore($node, $model->getPropertyNamesForHistory());
            Mysql::commit();
            return $node;
        } catch (\Throwable $e) {
            Mysql::rollBack();
            throw $e;
        }
    }

    public function increment(string $tenantId, string $key, int $min = 0, bool $transaction = true): int
    {
        return Mysql::increment($tenantId, $key, $min, $transaction);
    }

    /**
     * @throws JsonException
     */
    public function addJob(string $tenantId, string $worker, ?string $model, ?array $data = null): string
    {
        Mysql::beginTransaction();
        try {
            if ($data !== null) {
                $data = json_encode($data, JSON_THROW_ON_ERROR);
            }
            $sql = 'INSERT INTO `job` (`id`, `tenant_id`, `worker`, `model`, `status`, `data`) VALUES (:id, :tenant_id, :worker, :model, :status, :data)';
            $params = [
                'id' => DB::id(),
                'tenant_id' => $tenantId,
                'worker' => $worker,
                'model' => $model,
                'status' => Job::NEW,
                'data' => $data
            ];
            Mysql::execute($sql, $params);
            Mysql::commit();
            return $params['id'];
        } catch (\Throwable $e) {
            Mysql::rollBack();
            throw $e;
        }
    }

    /**
     * @throws JsonException
     */
    public function finishJob(string $id, ?array $result = null, bool $failed = false): void
    {
        Mysql::beginTransaction();
        try {
            if ($result !== null) {
                 $result = json_encode($result, JSON_THROW_ON_ERROR);
            }
            $sql = 'UPDATE `job` SET `status` = :status, `result` = :result, finished_at = now() WHERE `id` = :id';
            $params = [
                'status' => $failed ? Job::FAILED : Job::FINISHED,
                'result' => $result,
                'id' => $id
            ];
            Mysql::execute($sql, $params);
            Mysql::commit();
        } catch (\Throwable $e) {
            Mysql::rollBack();
            throw $e;
        }
    }

    /**
     * @throws JsonException
     */
    public function takeJob(): ?Job
    {
        Mysql::beginTransaction();
        try {
            $sql = 'SELECT * FROM `job` WHERE `status` = :status AND (`run_at` IS NULL OR `run_at` < now())  ORDER BY `created_at` ASC LIMIT 1 FOR UPDATE';
            $params = ['status' => Job::NEW];
            $dto = Mysql::fetch($sql, $params);

            if ($dto === null) {
                Mysql::rollBack();
                return null;
            }
            $sql = 'UPDATE `job` SET `status` = :status, `started_at` = now() WHERE `id` = :id';
            $params = ['id' => $dto->id, 'status' => Job::RUNNING];
            Mysql::execute($sql, $params);

            $job = new Job();
            $job->id = $dto->id;
            $job->tenantId = $dto->tenant_id;
            $job->worker = $dto->worker;
            if ($dto->data === null) {
                $job->data = null;
            } else {
                $job->data = json_decode($dto->data, true, 512, JSON_THROW_ON_ERROR);
            }
            $job->result = null;
            Mysql::commit();
            return $job;
        } catch (\Throwable $e) {
            Mysql::rollBack();
            throw $e;
        }
    }

    public function getJob(?string $tenantId, string $name, string $id): ?stdClass
    {
        $sql = 'SELECT * FROM `job` WHERE `id` = :id AND `worker` = :worker';
        $params = ['id' => $id, 'worker' => $name];
        if ($tenantId !== null) {
            $sql .= ' AND `tenant_id` = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }
        $dto = Mysql::fetch($sql, $params);
        if ($dto === null) {
            return null;
        }
        return Job::parse($dto);
    }

    public function findJobs(?string $tenantId, string $name, array $args): stdClass
    {
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        if ($page < 1) {
            throw new Error('Page cannot be less than 1');
        }
        $offset = ($page - 1) * $limit;

        $builder = MysqlFlatQueryBuilder::forTable('job');
        $builder->where(['column' => 'worker', 'operator' => '=', 'value' => $name]);
        if ($tenantId !== null) {
            $builder->tenant($tenantId);
        }
        if (isset($args['where'])) {
            $builder->where(MysqlConverter::convertWhereValues($this->jobModel(), $args['where']));
        }
        $builder->orderBy($args['orderBy'] ?? []);
        $builder->limit($limit, $offset);

        $jobs = [];
        foreach (Mysql::fetchAll($builder->toSql(), $builder->getParameters()) as $dto) {
            $jobs[] = Job::parse($dto);
        }
        $total = (int)Mysql::fetchColumn($builder->toCountSql(), $builder->getParameters());

        $result = new stdClass();
        $result->paginatorInfo = PaginatorInfo::create(count($jobs), $page, $limit, $total);
        $result->data = $jobs;
        return $result;
    }

    public function findHistory(?string $tenantId, array $args): stdClass
    {
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        if ($page < 1) {
            throw new Error('Page cannot be less than 1');
        }
        $offset = ($page - 1) * $limit;

        $builder = MysqlFlatQueryBuilder::forTable('history');
        if ($tenantId !== null) {
            $builder->tenant($tenantId);
        }
        if (isset($args['where'])) {
            $builder->where(MysqlConverter::convertWhereValues($this->historyModel(), $args['where']));
        }
        $builder->orderBy($args['orderBy'] ?? []);
        $builder->limit($limit, $offset);

        $history = [];
        foreach (Mysql::fetchAll($builder->toSql(), $builder->getParameters()) as $dto) {
            if (isset($dto->result)) {
                $dto->result = json_decode($dto->result, false, 512, JSON_THROW_ON_ERROR);
            }
            $dto->created_at = Carbon::parse($dto->created_at)->getPreciseTimestamp(3);
            $history[] = $dto;
        }
        $total = (int)Mysql::fetchColumn($builder->toCountSql(), $builder->getParameters());

        $result = new stdClass();
        $result->paginatorInfo = PaginatorInfo::create(count($history), $page, $limit, $total);
        $result->data = $history;
        return $result;
    }


    protected function jobModel(): Model
    {
        $job = new Model();
        unset($job->updated_at, $job->deleted_at);
        $job->worker = Field::string();
        $job->model = Field::string()->nullable();
        $job->status = Field::enum(Job::allStatuses());
        $job->data = Field::string()->nullable();
        $job->result = Field::string()->nullable();
        $job->run_at = Field::dateTime()->nullable();
        $job->started_at = Field::dateTime()->nullable();
        $job->finished_at = Field::dateTime()->nullable();
        return $job;
    }

    protected function historyModel(): Model
    {
        $history = new Model();
        unset($history->updated_at, $history->deleted_at);
        $history->number = Field::int();
        $history->node_id = Field::string();
        $history->model = Field::string();
        $history->sub = Field::string()->nullable();
        $history->ip = Field::string()->nullable();
        $history->user_agent = Field::string()->nullable();
        $history->change_type = Field::enum(['create', 'update', 'massUpdate', 'delete', 'restore'])->nullable();
        $history->changes = Field::string();
        $history->preceding_hash = Field::string()->nullable();
        $history->hash = Field::string();
        return $history;
    }

    /**
     * @param string $tenantId
     * @param string $name
     * @param string[] $ids
     * @param string $resultType
     * @return Closure
     * @codeCoverageIgnore
     */
    protected function getClosure(string $tenantId, string $name, array $ids, string $resultType)
    {
        return function () use ($tenantId, $ids, $resultType) {
            return $this->loadNodes($tenantId, $ids, $resultType);
        };
    }

    /**
     * @param Model $model
     * @param string $name
     * @param string|null $tenantId
     * @param array $args
     * @param string $resultType
     * @param int|null $limit
     * @param int|null $offset
     * @return stdClass
     */
    protected function getIdsForWhere(
        Model $model,
        string $name,
        ?string $tenantId,
        array $args,
        string $resultType,
        ?int $limit = null,
        ?int $offset = null,
    ): stdClass {
        $query = MysqlQueryBuilder::forModel($model, $name)
            ->tenant($tenantId)
            ->select(['id'])
            ->limit($limit ?? 99999, $offset ?? 0);

        if (isset($args['where'])) {
            $args['where'] = MysqlConverter::convertWhereValues($model, $args['where']);
        }

        $query->select(['id'])
            ->where($args['where'] ?? null)
            ->whereMode($args['whereMode'] ?? 'AND')
            ->orderBy($args['orderBy'] ?? [])
            ->search($args['search'] ?? null)
            ->searchLoosely($args['searchLoosely'] ?? null);

        foreach (get_object_vars($model) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $relatedClassname = $relation->classname;
            $relatedModel = new $relatedClassname();
            $relatedWhere = MysqlConverter::convertWhereValues($relatedModel, $args['where' . ucfirst($key)]);

            $query->whereHas($tenantId, $relatedModel, $relation->name, $relation->type, $relatedWhere);
        }

        match ($resultType) {
            Result::ONLY_SOFT_DELETED => $query->onlySoftDeleted(),
            Result::WITH_TRASHED => $query->withTrashed(),
            default => null
        };

        $ids = [];
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
            $ids[] = $row->id;
        }

        if ($limit === null) {
            $total = count($ids);
        } else {
            $total = (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());
        }

        return (object)[
            'ids' => $ids,
            'total' => $total,
        ];
    }

    /**
     * @param string $tenantId
     * @param Model $model
     * @param string $name
     * @param mixed[] $data
     * @param string|null $id
     * @throws Error
     */
    protected function checkUnique(string $tenantId, Model $model, string $name, array $data, string $id = null): void
    {
        foreach (get_object_vars($model) as $key => $field) {
            if (!$field instanceof Field || !isset($data[$key])) {
                continue;
            }
            if ($field->unique === true) {
                $query = MysqlQueryBuilder::forModel($model, $name)->tenant($tenantId);
                $query->where(['column' => $key, 'operator' => '=', 'value' => $data[$key]]);
                if ($field->uniqueIgnoreTrashed === false) {
                    $query->withTrashed();
                }
                if ($id !== null) {
                    $query->where(['column' => 'id', 'operator' => '!=', 'value' => $id]);
                }
                $total = (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());
                if ($total >= 1) {
                    throw new Error(
                        'Property "' . $key . '" must be unique, but value "' . (string)$data[$key] . '" already exists.'
                    );
                }
            }
        }
    }

    protected function checkIncrements(string $tenantId, Model $model, string $name, array $data): array
    {
        foreach (get_object_vars($model) as $key => $field) {
            if (!$field instanceof Field) {
                continue;
            }
            if ($field->type === Field::AUTO_INCREMENT) {
                $data[$key] = $this->increment($tenantId, $name . '.' . $key, 0, false);
            }
        }
        return $data;
    }

    /**
     * @param Model $model
     * @param mixed[] $updates
     * @throws Error
     */
    protected function checkNull(Model $model, array $updates): void
    {
        foreach (get_object_vars($model) as $key => $item) {
            if ($item instanceof Field && array_key_exists(
                    $key,
                    $updates
                ) && $updates[$key] === null && $item->null === false) {
                throw new Error('Field ' . $key . ' is not nullable.');
            }
        }
    }

    protected function storeFiles(Model $model, string $name, array $data, string $id): array
    {
        foreach (get_object_vars($model) as $key => $item) {
            if (
                !$item instanceof Field
                || !array_key_exists($key, $data)
                || $item->type !== Field::FILE
            ) {
                continue;
            }
            $oldValue = $this->getOldValue($name, $id, $key);
            if ($oldValue !== null) {
                File::delete($name, $id, $key, $oldValue);
            }
            if ($data[$key] !== null) {
                $data[$key] = File::store($name, $id, $key, $data[$key]);
            }
        }
        return $data;
    }

    protected function getOldValue(string $name, string $id, string $key): ?string
    {
        $sql = 'SELECT value_string FROM `node_property` WHERE `node_id` = :id AND `model` = :model AND `property` = :property AND deleted_at IS NULL';
        $params = [
            ':id' => $id,
            ':model' => $name,
            ':property' => $key
        ];
        $property = Mysql::fetch($sql, $params);
        return $property->value_string ?? null;
    }

    protected function retrieveFiles(stdClass $data): stdClass
    {
        $model = model($data->model);
        foreach (get_object_vars($model) as $key => $item) {
            if (
                !$item instanceof Field
                || $item->type !== Field::FILE
                || ($data->$key ?? null) === null
            ) {
                continue;
            }
            //can't use closure here, because there are subfields - graphql-php only allows closures at leaf-nodes
            //$value = $data->$key;
            //$data->$key = function() use($name, $id, $key, $value) {File::retrieve($name, $id, $key, $value);};
            $data->$key = File::retrieve($data->model, $data->id, $key, $data->$key);
        }
        return $data;
    }

    protected function softDeleteFiles(string $name, stdClass $node): void
    {
        $model = model($name);
        foreach (get_object_vars($model) as $key => $item) {
            if ($item instanceof Field && $item->type === Field::FILE) {
                // $node contains the replaced file-object instead of the original db-value
                // thus the original value has to be loaded from DB
                $oldValue = $this->getOldValue($name, $node->id, $key);
                if ($oldValue !== null) {
                    File::softDelete($name, $node->id, $key, $oldValue);
                }
            }
        }
    }

    protected function restoreFiles(string $name, stdClass $node): void
    {
        $model = model($name);
        foreach (get_object_vars($model) as $key => $item) {
            if ($item instanceof Field && $item->type === Field::FILE) {
                $oldValue = $this->getOldValue($name, $node->id, $key);
                if ($oldValue !== null) {
                    File::restore($name, $node->id, $key, $oldValue);
                }
            }
        }
    }


    public function loadEdges(array $ids): array
    {
        return Mysql::edgeReader()->loadEdges($ids);
    }

    public function findEdges(?string $tenantId, string $nodeId, Relation $relation, array $args): array|stdClass
    {
        return Mysql::edgeReader()->findEdges($tenantId, $nodeId, $relation, $args);
    }
}
