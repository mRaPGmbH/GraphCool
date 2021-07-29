<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DataProvider;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\Enums\ResultType;
use Mrap\GraphCool\Types\Objects\PaginatorInfoType;
use Mrap\GraphCool\Utils\StopWatch;
use Ramsey\Uuid\Uuid;
use stdClass;

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

    public function findAll(?string $tenantId, string $name, array $args): stdClass
    {
        StopWatch::start(__METHOD__);
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        if ($page < 1)  {
            throw new Error('Page cannot be less than 1');
        }
        $offset = ($page - 1) * $limit;
        $resultType = $args['result'] ?? ResultType::DEFAULT;

        $model = Model::get($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant($tenantId);

        if (isset($args['where'])) {
            $args['where'] = MysqlConverter::convertWhereValues($model, $args['where']);
        }

        $query->select(['id'])
            ->limit($limit, $offset)
            ->where($args['where'] ?? null)
            ->orderBy($args['orderBy'] ?? [])
            ->search($args['search'] ?? null);

        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $relatedClassname = $relation->classname;
            $relatedModel = new $relatedClassname();
            $relatedWhere = MysqlConverter::convertWhereValues($relatedModel, $args['where'.ucfirst($key)]);

            $query->whereHas($relatedModel, $relation->name, $relation->type, $relatedWhere);
        }

        match($resultType) {
            'ONLY_SOFT_DELETED' => $query->onlySoftDeleted(),
            'WITH_TRASHED' => $query->withTrashed(),
            default => null
        };

        $ids = [];
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
            $ids[] = $row->id;
        }
        $total = (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());

        $result = new stdClass();
        $result->paginatorInfo = PaginatorInfoType::create(count($ids), $page, $limit, $total);
        StopWatch::stop(__METHOD__);
        $result->data = $this->loadAll($tenantId, $name, $ids, $resultType);
        return $result;
    }

    public function getMax(?string $tenantId, string $name, string $key): float|bool|int|string
    {
        $model = Model::get($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant($tenantId);

        $valueType = match ($model->$key->type) {
            Type::BOOLEAN, Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET => 'value_int',
            Type::FLOAT, Field::DECIMAL => 'value_float',
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

    public function loadAll(?string $tenantId, string $name, array $ids, ?string $resultType = ResultType::DEFAULT): array
    {
        $result = [];
        foreach ($ids as $id) {
            $result[] = $this->load($tenantId, $name, $id, $resultType);
        }
        return $result;
    }

    public function load(?string $tenantId, string $name, string $id, ?string $resultType = ResultType::DEFAULT): ?stdClass
    {
        return Mysql::nodeReader()->load($tenantId, $name, $id, $resultType);
    }

    public function insert(string $tenantId, string $name, array $data): stdClass
    {
        $model = Model::get($name);
        $data = $model->beforeInsert($tenantId, $data);
        $this->checkUnique($tenantId, $model, $name, $data);

        $id = Uuid::uuid4()->toString();
        Mysql::nodeWriter()->insert($tenantId, $name, $id, $data);

        $loaded = $this->load($tenantId, $name, $id);
        if ($loaded !== null) {
            $model->afterInsert($loaded);
        }
        return $loaded;
    }

    public function update(string $tenantId, string $name, array $data): ?stdClass
    {
        $model = Model::get($name);
        $this->checkIfNodeExists($tenantId, $model, $name, $data['id']);
        $updates = $data['data'] ?? [];
        $updates = $model->beforeUpdate($tenantId, $data['id'], $updates);
        $this->checkUnique($tenantId, $model, $name, $updates, $data['id']);
        $this->checkNull($model, $updates);

        Mysql::nodeWriter()->update($tenantId, $name, $data['id'], $updates);

        $loaded = $this->load($tenantId, $name, $data['id'], ResultType::WITH_TRASHED);
        if ($loaded !== null) {
            $model->afterUpdate($loaded);
        }
        return $loaded;
    }

    protected function checkIfNodeExists(string $tenantId, Model $model, string $name, string $id): void
    {
        $query = MysqlQueryBuilder::forModel($model, $name)
            ->tenant($tenantId)
            ->where(['column' => 'id', 'operator' => '=', 'value' => $id])
            ->withTrashed();
        if ((int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters()) === 0) {
            throw new Error($name . ' with ID ' . $id . ' not found.');
        }
    }

    public function updateMany(string $tenantId, string $name, array $data): stdClass
    {
        $model = Model::get($name);
        $updateData = $data['data'] ?? [];
        $resultType = $data['result'] ?? ResultType::DEFAULT;
        $this->checkNull($model, $updateData);
        $ids = $this->getIdsForWhere($model, $name, $tenantId, $data['where'] ?? null, $resultType);
        $result = Mysql::nodeWriter()->updateMany($tenantId, $name, $ids, $updateData, $data['where'] ?? null, $resultType);

        $closure = function() use ($tenantId, $name, $ids, $resultType) {
            return $this->loadAll($tenantId, $name, $ids, $resultType);
        };
        $model->afterBulkUpdate($closure);

        return $result;
    }

    protected function getIdsForWhere(Model $model, string $name, string $tenantId, ?array $where, string $resultType): array
    {
        $ids = [];
        $query = MysqlQueryBuilder::forModel($model, $name)
            ->tenant($tenantId)
            ->select(['id'])
            ->where($where ?? null);
        match($resultType) {
            ResultType::ONLY_SOFT_DELETED => $query->onlySoftDeleted(),
            ResultType::WITH_TRASHED => $query->withTrashed(),
            default => null
        };
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
            $ids[] = $row->id;
        }
        return $ids;
    }


    public function delete(string $tenantId, string $name, string $id): ?stdClass
    {
        $node = $this->load($tenantId, $name, $id);
        Mysql::nodeWriter()->delete($tenantId, $id);
        $model = Model::get($name);
        $model->afterDelete($node); // TODO: nullpointer exception?
        return $node;
    }

    public function restore(?string $tenantId, string $name, string $id): stdClass
    {
        Mysql::nodeWriter()->restore($tenantId, $id);
        $node = $this->load($tenantId, $name, $id);
        $model = Model::get($name);
        $model->afterUpdate($node);  // TODO: nullpointer exception?
        return $node;
    }

    protected function checkUnique(string $tenantId, Model $model, string $name, array $data, string $id = null): void
    {
        foreach ($model as $key => $field) {
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
                    throw new Error('Property "' . $key . '" must be unique, but value "' . (string)$data[$key] . '" already exists.');
                }
            }
        }
    }

    protected function checkNull(Model $model, array $updates): void
    {
        foreach ($model as $key => $item) {
            if ($item instanceof Field && array_key_exists($key, $updates) && $updates[$key] === null && $item->null === false) {
                throw new Error('Field ' . $key . ' is not nullable.');
            }
        }
    }

}