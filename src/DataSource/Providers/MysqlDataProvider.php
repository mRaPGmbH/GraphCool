<?php
declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Providers;

use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DataProvider;
use Mrap\GraphCool\DataSource\QueryBuilders\MysqlQueryBuilder;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\Enums\ResultType;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\StopWatch;
use Mrap\GraphCool\Utils\TimeZone;
use PDO;
use PDOException;
use PDOStatement;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use stdClass;

class MysqlDataProvider extends DataProvider
{

    protected PDO $pdo;
    protected array $statements = [];
    protected int $search = 0;

    public function migrate(): void
    {
        $this->connectPdo();

        $sql = 'SET sql_notes = 0';
        $this->pdo()->exec($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `node` (
              `id` char(36) NOT NULL COMMENT \'uuid\',
              `tenant_id` int(11) NOT NULL,
              `model` varchar(255) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $this->pdo()->exec($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `node_property` (
              `node_id` char(36) NOT NULL,
              `model` varchar(255) NOT NULL,
              `property` varchar(255) NOT NULL,
              `value_int` bigint(20) DEFAULT NULL,
              `value_string` longtext,
              `value_float` double DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`node_id`,`property`),
              CONSTRAINT `node_property_ibfk_2` FOREIGN KEY (`node_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $this->pdo()->exec($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `edge` (
              `parent_id` char(36) NOT NULL COMMENT \'node.id\',
              `child_id` char(36) NOT NULL COMMENT \'node.id\',
              `tenant_id` int(11) NOT NULL,
              `parent` varchar(255) NOT NULL,
              `child` varchar(255) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`parent_id`,`child_id`),
              KEY `child_id` (`child_id`),
              CONSTRAINT `edge_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `node` (`id`) ON DELETE CASCADE,
              CONSTRAINT `edge_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $this->pdo()->exec($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `edge_property` (
              `parent_id` char(36) NOT NULL COMMENT \'node.id\',
              `child_id` char(36) NOT NULL COMMENT \'node.id\',
              `parent` varchar(255) NOT NULL COMMENT \'node.name\',
              `child` varchar(255) NOT NULL COMMENT \'node.name\',
              `property` varchar(255) NOT NULL,
              `value_int` bigint(20) DEFAULT NULL,
              `value_string` longtext,
              `value_float` double DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`parent_id`,`child_id`,`child`),
              KEY `child_id` (`child_id`),
              KEY `key` (`child`),
              CONSTRAINT `edge_property_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `node` (`id`) ON DELETE CASCADE,
              CONSTRAINT `edge_property_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $this->pdo()->exec($sql);

        $sql = 'SET sql_notes = 1';
        $this->pdo()->exec($sql);
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

        $model = $this->getModel($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant($tenantId);

        if (isset($args['where'])) {
            $args['where'] = $this->convertWhereValues($model, $args['where']);
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
            $relatedWhere = $this->convertWhereValues($relatedModel, $args['where'.ucfirst($key)]);

            $query->whereHas($relatedModel, $relation->name, $relation->type, $relatedWhere);
        }

        match($resultType) {
            'ONLY_SOFT_DELETED' => $query->onlySoftDeleted(),
            'WITH_TRASHED' => $query->withTrashed(),
            default => null
        };

        //var_dump($query->toSql());
        //var_dump($query->getParameters());

        $statement = $this->statement($query->toSql());
        $statement->execute($query->getParameters());
        $ids = [];
        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $row) {
            $ids[] = $row->id;
        }

        $statement = $this->statement($query->toCountSql());
        $statement->execute($query->getParameters());
        $total = (int)$statement->fetchColumn();

        $result = new stdClass();
        $result->paginatorInfo = $this->getPaginatorInfo(count($ids), $page, $limit, $total);
        StopWatch::stop(__METHOD__);
        $result->data = $this->loadAll($tenantId, $name, $ids, $resultType);
        return $result;
    }

    public function getMax(?string $tenantId, string $name, string $key): float|bool|int|string
    {
        $model = $this->getModel($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant($tenantId);

        $valueType = match ($model->$key->type) {
            Type::BOOLEAN, Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET => 'value_int',
            Type::FLOAT, Field::DECIMAL => 'value_float',
            default => 'value_string'
        };

        $query->selectMax($key, 'max', $valueType)
            ->withTrashed();
        $statement = $this->statement($query->toSql());
        $statement->execute($query->getParameters());
        $result = $statement->fetch(PDO::FETCH_OBJ);

        $property = new stdClass();
        $property->$valueType = $result->max;

        return $this->convertDatabaseTypeToOutput($model->$key, $property);
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
        StopWatch::start(__METHOD__);
        $node = $this->fetchNode($tenantId, $id, $resultType);
        if ($node === null) {
            return null;
        }
        if ($node->model !== $name) {
            throw new RuntimeException(
                'Class mismatch: expected "' . $name . '" but found "'
                . $node->model . '" instead (id:"' . $id . '")'
            );
        }
        $model = $this->getModel($name);
        foreach ($this->fetchNodeProperties($id) as $property) {
            $key = $property->property;
            if (!property_exists($model, $key)) {
                continue;
            }
            /** @var Field $field */
            $field = $model->$key;
            if ($field instanceof Relation) {
                continue;
            }
            $node->$key = $this->convertDatabaseTypeToOutput($field, $property);
        }

        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $node->$key = function (array $args) use ($id, $relation, $tenantId) {
                $result = $this->findRelatedNodes($tenantId, $id, $relation, $args);
                if (
                    $result !== null
                    && ($relation->type === Relation::HAS_ONE || $relation->type === Relation::BELONGS_TO)
                ) {
                    return $result[0] ?? null;
                }
                return $result;
            };
        }
        StopWatch::stop(__METHOD__);
        return $node;
    }

    public function insert(string $tenantId, string $name, array $data): stdClass
    {
        $model = $this->getModel($name);
        $data = $model->beforeInsert($tenantId, $data);
        $this->checkUnique($tenantId, $model, $name, $data);

        $id = Uuid::uuid4()->toString();
        $this->insertNode($tenantId, $id, $name);

        foreach ($model as $key => $item) {
            if (!array_key_exists($key, $data) && (
                    $item instanceof Relation ||
                    $item->null || in_array(
                            $item->type,
                            [
                                Type::ID,
                                Field::UPDATED_AT,
                                Field::DELETED_AT,
                                Field::CREATED_AT
                            ],
                            true
                        ))) {
                continue;
            }
            if ($item instanceof Relation && ($item->type === Relation::BELONGS_TO || $item->type === Relation::BELONGS_TO_MANY)) {
                $inputs = $data[$key];
                if ($item->type === Relation::BELONGS_TO) {
                    $parentId = $inputs['id'];
                    unset($inputs['id']);
                    $this->deleteAllRelations([$id], $item->name);
                    $this->insertOrUpdateBelongsRelation($tenantId, $item, $inputs, $parentId, [$id], $name);
                } else {
                    foreach ($inputs as $input) {
                        $this->insertOrUpdateBelongsManyRelation($tenantId, $item, $input, [$id], $name);
                    }
                }
            } elseif ($item instanceof Field) {
                $this->insertOrUpdateModelField($item, $data[$key] ?? null, $id, $name, $key);
            }
        }
        $loaded = $this->load($tenantId, $name, $id);
        if ($loaded !== null) {
            $model->afterInsert($loaded);
        }
        return $loaded;
    }

    public function update(string $tenantId, string $name, array $data): ?stdClass
    {
        $model = $this->getModel($name);
        $updates = $data['data'] ?? [];
        $updates = $model->beforeUpdate($tenantId, $data['id'], $updates);
        $this->checkUnique($tenantId, $model, $name, $updates, $data['id']);
        $this->checkNull($model, $updates);
        if (!$this->updateNode($tenantId, $data['id'])) {
            throw new Error($name . ' with ID ' . $data['id'] . ' not found.');
        }

        foreach ($model as $key => $item) {
            if (!array_key_exists($key, $updates)) {
                continue;
            }
            if ($item instanceof Relation && ($item->type === Relation::BELONGS_TO || $item->type === Relation::BELONGS_TO_MANY)) {
                $inputs = $updates[$key];
                if ($item->type === Relation::BELONGS_TO) {
                    $parentId = $inputs['id'];
                    unset($inputs['id']);
                    $this->deleteAllRelations([$data['id']], $item->name);
                    $this->insertOrUpdateBelongsRelation($tenantId, $item, $inputs, $parentId, [$data['id']], $name);
                } else {
                    foreach ($inputs as $input) {
                        $this->insertOrUpdateBelongsManyRelation($tenantId, $item, $input, [$data['id']], $name);
                    }
                }
            }
        }

        $updates = $model->afterRelationUpdateButBeforeNodeUpdate($tenantId, $data['id'], $updates);

        foreach ($model as $key => $item) {
            if (!array_key_exists($key, $updates)) {
                continue;
            }
            if ($item instanceof Field) {
                $this->insertOrUpdateModelField($item, $updates[$key], $data['id'], $name, $key);
            }
        }

        $loaded = $this->load($tenantId, $name, $data['id'], ResultType::WITH_TRASHED);
        if ($loaded !== null) {
            $model->afterUpdate($loaded);
        }
        return $loaded;
    }

    public function updateMany(string $tenantId, string $name, array $data): stdClass
    {
        $model = $this->getModel($name);
        $resultType = $data['result'] ?? ResultType::DEFAULT;

        $updateData = $data['data'] ?? [];
        $this->checkNull($model, $updateData);

        $updates = [
            'deleted_at' => null
        ];
        $relations = [];
        foreach ($model as $key => $item) {
            if (!array_key_exists($key, $updateData)) {
                continue;
            }
            if ($item instanceof Field) {
                if ($item->unique === true && $updateData[$key] !== null) {
                    throw new Error('Field ' . $key . ' is defined as unique cannot be set to the same value for many items.');
                }
                $updates[$key] = $updateData[$key];
            } elseif ($item instanceof Relation) {
                $relations[$key] = $updateData[$key];
            }
        }
        // TODO: $updates = $model->beforeUpdate($updates); <- beforeUpdate has to be free of individual (single-entity) stuff

        $ids = null;
        if (count($relations) > 0) {
            $ids = $this->getIdsForWhere($model, $name, $tenantId, $data['where'], $resultType);
            foreach ($relations as $key => $relationDatas) {
                foreach ($relationDatas as $relationData) {
                    $this->insertOrUpdateBelongsManyRelation($tenantId, $model->$key, $relationData, $ids, $key);
                }
            }
        }

        $query = MysqlQueryBuilder::forModel($model, $name)
            ->tenant($tenantId)
            ->update($updates)
            ->where($data['where'] ?? null)
            ->withTrashed();

        $statement = $this->statement($query->toCountSql());
        $statement->execute($query->getParameters());
        $result = new stdClass();
        $result->updated_rows = (int)$statement->fetchColumn();

        $statement = $this->statement($query->toSql());
        $statement->execute($query->getUpdateParameters());

        if ($ids === null) {
            $where = $data['where'] ?? null;
            $ids = function() use ($model, $name, $tenantId, $where, $resultType) {
                return $this->getIdsForWhere($model, $name, $tenantId, $where, $resultType);
            };
        }
        $closure = function() use ($tenantId, $name, $ids, $resultType) {
            if (is_callable($ids)) {
                $ids = $ids();
            }
            $this->loadAll($tenantId, $name, $ids, $resultType);
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
        $statement = $this->statement($query->toSql());
        $statement->execute($query->getUpdateParameters());
        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $row) {
            $ids[] = $row->id;
        }
        return $ids;
    }

    protected function convertWhereValues(Model $model, ?array &$where): ?array
    {
        if ($where === null) {
            return $where;
        }
        if (isset($where['column']) && array_key_exists('value', $where)) {
            $column = $where['column'];
            /** @var Field $field */
            $field = $model->$column;
            if (is_array($where['value'])) {
                foreach ($where['value'] as $key => $value) {
                    // TODO: is there a better way to do this? are there other types that need special treatment?
                    if ($field->type === Field::DATE || $field->type === Field::TIME || $field->type === Field::DATE_TIME) {
                        $value = strtotime($value) * 1000;
                    }
                    $where['value'][$key] = $this->convertInputTypeToDatabase($model->$column, $value);
                }
            } else {
                // TODO: is there a better way to do this? are there other types that need special treatment?
                if ($field->type === Field::DATE || $field->type === Field::TIME || $field->type === Field::DATE_TIME) {
                    $where['value'] = strtotime($where['value']) * 1000;
                }
                $where['value'] = $this->convertInputTypeToDatabase($model->$column, $where['value']);
            }
        }
        if (isset($where['AND'])) {
            foreach($where['AND'] as $key => $subWhere) {
                $where['AND'][$key] = $this->convertWhereValues($model, $subWhere);
            }
        }
        if (isset($where['OR'])) {
            foreach($where['OR'] as $key => $subWhere) {
                $where['OR'][$key] = $this->convertWhereValues($model, $subWhere);
            }
        }
        return $where;
    }

    protected function insertOrUpdateModelField(Field $field, $value, $id, $name, $key): void
    {
        if (($value === null || $value === '') && $field->null === true) {
            $this->deleteNodeProperty($id, $key);
            return;
        }
        $dbValue = $this->convertInputTypeToDatabase($field, $value);
        if (is_int($dbValue)) {
            $this->insertOrUpdateNodeProperty($id, $name, $key, $dbValue, null, null);
        } elseif (is_float($dbValue)) {
            $this->insertOrUpdateNodeProperty($id, $name, $key, null, null, $dbValue);
        } else {
            $this->insertOrUpdateNodeProperty($id, $name, $key, null, $dbValue, null);
        }
    }

    protected function insertOrUpdateBelongsRelation(string $tenantId, Relation $relation, array $data, string $parentId, array $childIds, string $childName): void
    {
        /*
        foreach ($relation as $key => $field)
        {
            if ($field->null === false && !isset($data[$key]) && ($field->default ?? null) === null) {
                // we are trying to update or inserting a relation with invalid data (non-nullable field is null)
                // this can only happen during the file-import: excel contains the column, but it's empty for some rows
                // in this case the relation should not be created (or deleted in case of update)
                $this->deleteRelations($childIds, [$parentId]);
                return;
            }
        }
        */
        foreach ($childIds as $childId) {
            $this->insertOrUpdateEdge($tenantId, $parentId, $childId, $relation->name, $childName);
            /** @var Field $field */
            foreach ($relation as $key => $field)
            {
                if (!isset($data[$key])) {
                    if (($field->default ?? null) !== null) {
                        $value = $this->convertInputTypeToDatabase($field, $field->default);
                    } else {
                        continue;
                    }
                } else {
                    $value = $this->convertInputTypeToDatabase($field, $data[$key]);
                }
                if (is_int($value)) {
                    $this->insertOrUpdateEdgeProperty($parentId, $childId, $relation->name, $childName, $key, $value, null, null);
                } elseif (is_float($value)) {
                    $this->insertOrUpdateEdgeProperty($parentId, $childId, $relation->name, $childName, $key, null, null, $value);
                } elseif (is_null($value)) {
                    $this->deleteEdgeProperty($parentId, $childId, $relation->name, $childName, $key);
                } else {
                    $this->insertOrUpdateEdgeProperty($parentId, $childId, $relation->name, $childName, $key, null, $value, null);
                }
            }
        }
    }

    protected function insertOrUpdateBelongsManyRelation(?string $tenantId, Relation $relation, array $data, array $childIds, string $childName): void
    {
        $mode = $data['mode'] ?? 'ADD';
        if ($mode === 'REPLACE') {
            $this->deleteAllRelations($childIds, $relation->name);
        }
        $classname = $relation->classname;
        $model = new $classname();
        $query = MysqlQueryBuilder::forModel($model, $relation->name)->tenant($tenantId);
        $query->select(['id'])
            ->search($data['search'] ?? null)
            ->where($data['where'] ?? []);
        $statement = $this->statement($query->toSql());
        $statement->execute($query->getParameters());
        if ($mode === 'REMOVE') {
            $ids = [];
            foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $row) {
                $ids[] = $row->id;
            }
            $this->deleteRelations($childIds, $ids);
        } else {
            foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $row) {
                $this->insertOrUpdateBelongsRelation($tenantId, $relation, $data, $row->id, $childIds, $childName);
            }
        }
    }

    protected function deleteAllRelations(array $childIds, string $parentName): void
    {
        if (count($childIds) === 0) {
            return;
        }
        $params = [];
        $i = 1;
        foreach ($childIds as $childId) {
            $params[':child_id' . $i++] = $childId;
        }
        $sql = 'UPDATE `edge` SET `deleted_at` = now() WHERE `child_id` IN ';
        $sql .= '(' . implode(',', array_keys($params)). ') ';
        $sql .= ' AND parent = :parent';
        $params[':parent'] = $parentName;

        $statement = $this->statement($sql);
        $statement->execute($params);
    }

    protected function deleteRelations(array $childIds, array $parentIds): void
    {
        if (count($childIds) === 0 || count($parentIds) === 0) {
            return;
        }
        $params = [];
        $i = 1;
        foreach ($childIds as $childId) {
            $params[':child_id' . $i++] = $childId;
        }
        $params2 = [];
        $i = 1;
        foreach ($parentIds as $parentId) {
            $params2[':parent_id' . $i++] = $parentId;
        }
        $sql = 'UPDATE `edge` SET `deleted_at` = now() WHERE `child_id` IN ';
        $sql .= '(' . implode(',', array_keys($params)) . ') ';
        $sql .= ' AND `parent_id` IN (' . implode(',', array_keys($params2)) . ')';

        $statement = $this->statement($sql);
        $statement->execute(array_merge($params, $params2));
    }

    protected function getPaginatorInfo(int $count, int $page, int $limit, int $total): stdClass
    {
        $paginatorInfo = new stdClass();
        $paginatorInfo->count = $count;
        $paginatorInfo->currentPage = $page;
        if ($total === 0) {
            $paginatorInfo->firstItem = 0;
        } else {
            $paginatorInfo->firstItem = 1;
        }
        $paginatorInfo->hasMorePages = $total > $page * $limit;
        $paginatorInfo->lastItem = $total;
        $paginatorInfo->lastPage = ceil($total / $limit);
        if ($paginatorInfo->lastPage < 1) {
            $paginatorInfo->lastPage = 1;
        }
        $paginatorInfo->perPage = $limit;
        $paginatorInfo->total = $total;
        return $paginatorInfo;
    }

    protected function statement(string $sql): PDOStatement
    {
        if (!isset($this->statements[$sql])) {
            $this->statements[$sql] = $this->pdo()->prepare($sql);
        }
        return $this->statements[$sql];
    }

    protected function pdo(): PDO
    {
        if (!isset($this->pdo)) {
            $connection = Env::get('DB_CONNECTION') . ':host=' . Env::get('DB_HOST') . ';port=' . Env::get('DB_PORT')
                . ';dbname=' . Env::get('DB_DATABASE');
            try {
                $this->pdo = new PDO(
                    $connection,
                    Env::get('DB_USERNAME'),
                    Env::get('DB_PASSWORD'),
                    [PDO::ATTR_PERSISTENT => true]
                );
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                throw new RuntimeException('Could not connect to database: ' . $connection . ' - user: ' . Env::get('DB_USERNAME') . ' ' . getenv('DB_USERNAME') . ' ' . print_r($_ENV, true));
            }
        }
        return $this->pdo;
    }

    protected function connectPdo(int $retries = 5): void
    {
        for ($i = 0; $i < $retries; $i++) {
            try {
                $this->pdo();
                return;
            } catch (RuntimeException $e) {
                sleep(1);
            }
        }
        $this->pdo();
    }


    protected function fetchNode(?string $tenantId, string $id, ?string $resultType = ResultType::DEFAULT): ?stdClass
    {
        // TODO: use queryBuilder
        $sql = 'SELECT * FROM `node` WHERE `id` = :id ';
        $params = [':id' => $id];
        if ($tenantId !== null) {
            $sql .= 'AND `node`.`tenant_id` = :tenant_id ';
            $params[':tenant_id'] = $tenantId;
        }
        $sql .= match($resultType) {
            'ONLY_SOFT_DELETED' => 'AND `deleted_at` IS NOT NULL ',
            'WITH_TRASHED' => '',
            default => 'AND `deleted_at` IS NULL ',
        };

        $statement = $this->statement($sql);
        $statement->execute($params);
        $node = $statement->fetch(PDO::FETCH_OBJ);
        if ($node === false) {
            return null;
        }

        $dates = ['updated_at', 'created_at', 'deleted_at'];
        foreach ($dates as $date) {
            if ($node->$date !== null) {
                $dateTime = Carbon::parse($node->$date);
                $node->$date = $dateTime->getPreciseTimestamp(3);
            }
        }
        return $node;
    }

    protected function getModel(string $name): Model
    {
        $classname = 'App\\Models\\' . $name;
        return new $classname();
    }

    protected function fetchNodeProperties(string $id): array
    {
        $sql = 'SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL';
        $statement = $this->statement($sql);
        $statement->execute([':node_id' => $id]);
        return $statement->fetchAll(PDO::FETCH_OBJ);
    }

    protected function fetchEdgeProperties(string $parentId, string $childId): array
    {
        $sql = 'SELECT * FROM `edge_property` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `deleted_at` IS NULL';
        $statement = $this->statement($sql);
        $statement->execute([
            'parent_id' => $parentId,
            'child_id' => $childId
        ]);
        return $statement->fetchAll(PDO::FETCH_OBJ);
    }


    protected function convertDatabaseTypeToOutput(Field $field, stdClass $property): float|bool|int|string
    {
        return match ($field->type) {
            Type::BOOLEAN => (bool)$property->value_int,
            Type::FLOAT => (double)$property->value_float,
            Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET => (int)$property->value_int,
            Field::DECIMAL => (float)($property->value_int/(10 ** $field->decimalPlaces)),
            default => (string)$property->value_string,
        };
    }


    protected function findRelatedNodes(?string $tenantId, string $id, Relation $relation, array $args): array|stdClass
    {
        StopWatch::start(__METHOD__);
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        if ($page < 1)  {
            throw new Error('Page cannot be less than 1');
        }
        $offset = ($page - 1) * $limit;
        $whereNode = $args['where'] ?? null;
        $whereEdge = $args['whereEdge'] ?? null;
        $search = $args['search'] ?? null;
        $orderBy = $args['orderBy'] ?? [];
        $resultType = $args['result'] ?? ResultType::DEFAULT;

        $edges = [];


        $query = MysqlQueryBuilder::forRelation($relation, [$id]);
        $query
            ->tenant($tenantId)
            ->select(['_child_id', '_parent_id'])
            ->limit($limit, $offset)
            ->where($whereEdge)
            ->whereRelated($whereNode)
            ->orderBy($orderBy)
            ->search($search);
        match($resultType) {
            'ONLY_SOFT_DELETED' => $query->onlySoftDeleted(),
            'WITH_TRASHED' => $query->withTrashed(),
            default => null
        };

        $statement = $this->statement($query->toSql());
        $statement->execute($query->getParameters());

        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $edgeIds) {
            $edge = $this->fetchEdge($tenantId, $edgeIds->parent_id, $edgeIds->child_id, $resultType);
            if ($edge === null) {
                // this should never happen!
                throw new RuntimeException('Edge was null: ' . print_r(['parent_id' => $edgeIds->parent_id, 'child_id' => $edgeIds->child_id, 'resultType' => $resultType], true));
            }
            $properties = $this->convertProperties($this->fetchEdgeProperties($edge->parent_id, $edge->child_id), $relation);
            foreach ($properties as $key => $value) {
                $edge->$key = $value;
            }
            if ($relation->type === Relation::HAS_ONE || $relation->type === Relation::HAS_MANY) {
                $edge->_node = $this->load($tenantId, $relation->name, $edge->child_id);
            } elseif ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::BELONGS_TO_MANY) {
                $edge->_node = $this->load($tenantId, $relation->name, $edge->parent_id);
            }
            $edges[] = $edge;
        }

        $statement = $this->statement($query->toCountSql());
        $statement->execute($query->getParameters());
        $total = (int)$statement->fetchColumn();

        StopWatch::stop(__METHOD__);
        if ($relation->type === Relation::HAS_MANY || $relation->type === Relation::BELONGS_TO_MANY) {
            $count = count($edges);
            return [
                'paginatorInfo' => $this->getPaginatorInfo($count, $page, $limit, $total),
                'edges' => $edges,
            ];
        }
        return $edges;
    }

    protected function fetchEdge(?string $tenantId, string $parentId, string $childId, ?string $resultType = ResultType::DEFAULT): ?stdClass
    {
        $sql = 'SELECT * FROM `edge` WHERE `parent_id` = :parent_id AND `child_id` = :child_id ';
        $parameters = [
            'parent_id' => $parentId,
            'child_id' => $childId
        ];

        if ($tenantId !== null) {
            $sql .= 'AND `tenant_id` = :tenant_id ';
            $parameters['tenant_id'] = $tenantId;
        }
        $sql .= match($resultType) {
            'ONLY_SOFT_DELETED' => 'AND `deleted_at` IS NOT NULL ',
            'WITH_TRASHED' => '',
            default => 'AND `deleted_at` IS NULL ',
        };

        $statement = $this->statement($sql);
        $statement->execute($parameters);
        $edge = $statement->fetch(PDO::FETCH_OBJ);
        if ($edge === false) {
            return null;
        }
        $dates = ['updated_at', 'created_at', 'deleted_at'];
        foreach ($dates as $date) {
            if ($edge->$date !== null) {
                $dateTime = Carbon::parse($edge->$date);
                $dateTime->setTimezone(TimeZone::get());
                $edge->$date = $dateTime->format('Y-m-d\TH:i:s.vp');
            }
        }
        return $edge;
    }

    protected function convertProperties(array $properties, Model|Relation $fieldSource): array
    {
        $result = [];
        foreach ($properties as $property) {
            $key = $property->property;
            if (!property_exists($fieldSource, $key)) {
                continue;
            }
            /** @var Field $field */
            $field = $fieldSource->$key;
            $result[$key] = $this->convertDatabaseTypeToOutput($field, $property);
        }
        return $result;
    }


    protected function insertNode(string $tenantId, string $id, string $name): bool
    {
        $sql = 'INSERT INTO `node` (`id`, `tenant_id`, `model`) VALUES (:id, :tenant_id, :model)';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':id'    => $id,
                ':tenant_id' => $tenantId,
                ':model' => $name
            ]
        );
    }

    protected function insertOrUpdateEdge(string $tenantId, string $parentId, string $childId, string $parent, string $child): bool
    {
        $sql = 'INSERT INTO `edge` (`parent_id`, `child_id`, `tenant_id`, `parent`, `child`) VALUES 
            (:parent_id, :child_id, :tenant_id, :parent, :child)
            ON DUPLICATE KEY UPDATE `deleted_at` = null';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':parent_id' => $parentId,
                ':child_id'  => $childId,
                ':tenant_id' => $tenantId,
                ':parent'    => $parent,
                ':child'     => $child,
            ]
        );
    }

    protected function insertOrUpdateNodeProperty(
        string $nodeId,
        string $name,
        string $propertyName,
        ?int $valueInt,
        ?string $valueString,
        ?float $valueFloat
    ): bool {
        $sql = 'INSERT INTO `node_property` (`node_id`, `model`, `property`, `value_int`, `value_string`, `value_float`) '
            . 'VALUES (:node_id, :model, :property, :value_int, :value_string, :value_float) '
            . 'ON DUPLICATE KEY UPDATE `value_int` = :value_int2, `value_string` = :value_string2, `value_float` = :value_float2, `deleted_at` = NULL';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':node_id'       => $nodeId,
                ':model'         => $name,
                ':property'      => $propertyName,
                ':value_int'     => $valueInt,
                ':value_string'  => $valueString,
                ':value_float'   => $valueFloat,
                ':value_int2'    => $valueInt,
                ':value_string2' => $valueString,
                ':value_float2'   => $valueFloat
            ]
        );
    }

    protected function insertOrUpdateEdgeProperty(
        string $parentId,
        string $childId,
        string $parent,
        string $child,
        string $propertyName,
        ?int $valueInt,
        ?string $valueString,
        ?float $valueFloat
    ): bool
    {
        $sql = 'INSERT INTO `edge_property` (`parent_id`, `child_id`, `parent`, `child`, `property`, `value_int`, `value_string`, `value_float`) '
            . 'VALUES (:parent_id, :child_id, :parent, :child, :property, :value_int, :value_string, :value_float) '
            . 'ON DUPLICATE KEY UPDATE `value_int` = :value_int2, `value_string` = :value_string2, `value_float` = :value_float2, `deleted_at` = NULL';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':parent_id'       => $parentId,
                ':child_id'       => $childId,
                ':parent'         => $parent,
                ':child'         => $child,
                ':property'      => $propertyName,
                ':value_int'     => $valueInt,
                ':value_string'  => $valueString,
                ':value_float'   => $valueFloat,
                ':value_int2'    => $valueInt,
                ':value_string2' => $valueString,
                ':value_float2'   => $valueFloat
            ]
        );
    }

    protected function deleteEdgeProperty(
        string $parentId,
        string $childId,
        string $parent,
        string $child,
        string $propertyName,
    ): bool
    {
        $sql = 'DELETE FROM `edge_property` WHERE `parent_id` = :parent_id AND `child_id` = :child_id '
            . 'AND `parent` = :parent AND `child` = :child AND `property` = :property';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':parent_id'       => $parentId,
                ':child_id'       => $childId,
                ':parent'         => $parent,
                ':child'         => $child,
                ':property'      => $propertyName,
            ]
        );
    }


    protected function updateNode(string $tenantId, string $id): bool
    {
        // TODO: maybe add model-name to this?
        $sql = 'UPDATE `node` SET `updated_at` = now() WHERE id = :id';
        $params = [':id' => $id];
        if ($tenantId !== null) {
            $sql .= ' AND `tenant_id` = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }
        $statement = $this->statement($sql);
        return $statement->execute($params);
    }

    protected function deleteNodeProperty(string $nodeId, string $propertyName): bool
    {
        $sql = 'UPDATE `node_property` SET `deleted_at` = now() '
            . 'WHERE `deleted_at` IS NULL AND `node_id` = :node_id AND `property` = :property';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':node_id'  => $nodeId,
                ':property' => $propertyName,
            ]
        );
    }

    public function delete(string $tenantId, string $name, string $id): ?stdClass
    {
        $node = $this->load($tenantId, $name, $id);
        $this->deleteNode($tenantId, $id);
        $this->deleteEdgesForNodeId($tenantId, $id);
        $model = $this->getModel($name);
        $model->afterDelete($node);
        return $node;
    }

    public function restore(?string $tenantId, string $name, string $id): stdClass
    {
        $this->restoreNode($tenantId, $id);
        $node = $this->load($tenantId, $name, $id);
        $model = $this->getModel($name);
        $model->afterUpdate($node);
        return $node;
    }

    protected function deleteNode(?string $tenantId, string $id): bool
    {
        $sql = 'UPDATE `node` SET `deleted_at` = now() WHERE `id` = :id';
        $params = [':id' => $id];
        if ($tenantId !== null) {
            $sql .= ' AND `tenant_id` = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }
        $statement = $this->statement($sql);
        return $statement->execute($params);
    }

    protected function deleteEdgesForNodeId(?string $tenantId, string $id): bool
    {
        $sql = 'UPDATE `edge` SET `deleted_at` = now() WHERE (`parent_id` = :id1 OR `child_id` = :id2)';
        $params = [':id1' => $id, ':id2' => $id];
        if ($tenantId !== null) {
            $sql .= ' AND `tenant_id` = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }
        $statement = $this->statement($sql);
        return $statement->execute($params);
    }

    protected function restoreNode(?string $tenantId, string $id): bool
    {
        $sql = 'UPDATE `node` SET `deleted_at` = NULL WHERE `id` = :id';
        $params = [':id' => $id];
        if ($tenantId !== null) {
            $sql .= ' AND `tenant_id` = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }
        $statement = $this->statement($sql);
        return $statement->execute($params);
    }

    protected function convertInputTypeToDatabase(Field $field, $value): float|int|string|null
    {
        if ($field->null === false && $value === null) {
            $value = $field->default ?? null;
            if (is_null($value)) {
                return null;
            }
        }
        return match ($field->type) {
            default => (string)$value,
            Field::DATE, Field::DATE_TIME, Field::TIME, Field::TIMEZONE_OFFSET, Type::BOOLEAN, Type::INT => (int)$value,
            Type::FLOAT => (float)$value,
            Field::DECIMAL => (int)(round($value * (10 ** $field->decimalPlaces))),
        };
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
                $statement = $this->statement($query->toCountSql());
                $statement->execute($query->getParameters());
                $total = (int)$statement->fetchColumn();
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