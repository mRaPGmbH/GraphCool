<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Closure;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DataProvider;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\ResultType;
use Mrap\GraphCool\Types\Objects\PaginatorInfoType;
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

    /**
     * @param string|null $tenantId
     * @param string $name
     * @param mixed[] $args
     * @return stdClass
     * @throws Error
     */
    public function findAll(?string $tenantId, string $name, array $args): stdClass
    {
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        if ($page < 1) {
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
            ->search($args['search'] ?? null)
            ->searchLoosely($args['searchLoosely'] ?? null);

        foreach (get_object_vars($model) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $relatedClassname = $relation->classname;
            $relatedModel = new $relatedClassname();
            $relatedWhere = MysqlConverter::convertWhereValues($relatedModel, $args['where' . ucfirst($key)]);

            $query->whereHas($relatedModel, $relation->name, $relation->type, $relatedWhere);
        }

        match ($resultType) {
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
        $result->data = $this->loadAll($tenantId, $name, $ids, $resultType);
        return $result;
    }

    public function getMax(?string $tenantId, string $name, string $key): float|bool|int|string
    {
        $model = Model::get($name);
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

    public function getSum(?string $tenantId, string $name, string $key): float|int
    {
        $model = Model::get($name);
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
        $model = Model::get($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant($tenantId);
        return (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());
    }


    /**
     * @param string|null $tenantId
     * @param string $name
     * @param string[] $ids
     * @param string|null $resultType
     * @return stdClass[]
     */
    public function loadAll(
        ?string $tenantId,
        string $name,
        array $ids,
        ?string $resultType = ResultType::DEFAULT
    ): array {
        $result = [];
        foreach ($ids as $id) {
            $node = $this->load($tenantId, $name, $id, $resultType);
            if ($node !== null) {
                $result[] = $node;
            }
        }
        return $result;
    }

    public function load(
        ?string $tenantId,
        string $name,
        string $id,
        ?string $resultType = ResultType::DEFAULT
    ): ?stdClass {
        $data = Mysql::nodeReader()->load($tenantId, $name, $id, $resultType);
        if ($data !== null) {
            $data = $this->retrieveFiles($name, $data, $id);
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
        $model = Model::get($name);
        $id = Uuid::uuid4()->toString();
        $data = $this->storeFiles($model, $name, $data, $id);
        $data = $model->beforeInsert($tenantId, $data);
        $this->checkUnique($tenantId, $model, $name, $data);

        Mysql::nodeWriter()->insert($tenantId, $name, $id, $data);

        $loaded = $this->load($tenantId, $name, $id);
        if ($loaded !== null) {
            $model->afterInsert($loaded);
        }
        return $loaded;
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
        $model = Model::get($name);
        $this->checkIfNodeExists($tenantId, $model, $name, (string)$data['id']);
        $updates = $data['data'] ?? [];
        $updates = $this->storeFiles($model, $name, $updates, $data['id']);
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

    /**
     * @param string $tenantId
     * @param string $name
     * @param mixed[] $data
     * @return stdClass
     * @throws Error
     */
    public function updateMany(string $tenantId, string $name, array $data): stdClass
    {
        $model = Model::get($name);
        $updateData = $data['data'] ?? [];
        $resultType = $data['result'] ?? ResultType::DEFAULT;
        $this->checkNull($model, $updateData);
        $ids = $this->getIdsForWhere($model, $name, $tenantId, $data['where'] ?? null, $resultType);
        $result = Mysql::nodeWriter()->updateMany($tenantId, $name, $ids, $updateData);

        $model->afterBulkUpdate($this->getClosure($tenantId, $name, $ids, $resultType));

        return $result;
    }

    public function delete(string $tenantId, string $name, string $id): ?stdClass
    {
        $node = $this->load($tenantId, $name, $id, ResultType::WITH_TRASHED);
        if ($node === null) {
            return null;
        }
        $this->softDeleteFiles($name, $node);
        Mysql::nodeWriter()->delete($tenantId, $id);
        $model = Model::get($name);
        $model->afterDelete($node);
        return $node;
    }

    public function restore(?string $tenantId, string $name, string $id): stdClass
    {
        $model = Model::get($name);
        $model->beforeRestore($tenantId, $id);
        Mysql::nodeWriter()->restore($tenantId, $id);
        $node = $this->load($tenantId, $name, $id);
        if ($node === null) {
            throw new Error($name . ' with ID ' . $id . ' not found.');
        }
        $model->afterRestore($node);
        return $node;
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
        return function () use ($tenantId, $name, $ids, $resultType) {
            return $this->loadAll($tenantId, $name, $ids, $resultType);
        };
    }

    /**
     * @param Model $model
     * @param string $name
     * @param string $tenantId
     * @param mixed[]|null $where
     * @param string $resultType
     * @return string[]
     */
    protected function getIdsForWhere(
        Model $model,
        string $name,
        string $tenantId,
        ?array $where,
        string $resultType
    ): array {
        $ids = [];
        $query = MysqlQueryBuilder::forModel($model, $name)
            ->tenant($tenantId)
            ->select(['id'])
            ->where($where ?? null);
        match ($resultType) {
            ResultType::ONLY_SOFT_DELETED => $query->onlySoftDeleted(),
            ResultType::WITH_TRASHED => $query->withTrashed(),
            default => null
        };
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
            $ids[] = $row->id;
        }
        return $ids;
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

    protected function retrieveFiles(string $name, stdClass $data, string $id): stdClass
    {
        $model = Model::get($name);
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
            $data->$key = File::retrieve($name, $id, $key, $data->$key);
        }
        return $data;
    }

    protected function softDeleteFiles(string $name, stdClass $node): void
    {
        $model = Model::get($name);
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


}