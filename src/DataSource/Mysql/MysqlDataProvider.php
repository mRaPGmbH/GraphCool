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
        $this->insertNode($tenantId, $id, $name);

        foreach ($model as $key => $item) {
            if (!$item instanceof Relation && !$item instanceof Field) {
                continue;
            }
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
        $model = Model::get($name);
        $this->checkIfNodeExists($tenantId, $model, $name, $data['id']);
        $updates = $data['data'] ?? [];
        $updates = $model->beforeUpdate($tenantId, $data['id'], $updates);
        $this->checkUnique($tenantId, $model, $name, $updates, $data['id']);
        $this->checkNull($model, $updates);

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

    protected function checkIfNodeExists(string $tenantId, Model $model, string $name, string $id): void
    {
        $query = MysqlQueryBuilder::forModel($model, $name)
            ->tenant($tenantId)
            ->where(['column' => 'id', 'operator' => '=', 'value' => $id])
            ->withTrashed();
        $statement = $this->statement($query->toCountSql());
        $statement->execute($query->getParameters());
        if ((int)$statement->fetchColumn() === 0) {
            throw new Error($name . ' with ID ' . $id . ' not found.');
        }
    }

    public function updateMany(string $tenantId, string $name, array $data): stdClass
    {
        $model = Model::get($name);
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

        $result = new stdClass();
        $result->updated_rows = (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());
        Mysql::execute($query->toSql(), $query->getUpdateParameters());

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
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
            $ids[] = $row->id;
        }
        return $ids;
    }

    protected function insertOrUpdateModelField(Field $field, $value, $id, $name, $key): void
    {
        if (($value === null || $value === '') && $field->null === true) {
            $this->deleteNodeProperty($id, $key);
            return;
        }
        [$valueInt, $valueString, $valueFloat] = MysqlConverter::convertInputTypeToDatabaseTriplet($field, $value);
        $this->insertOrUpdateNodeProperty($id, $name, $key, $valueInt, $valueString, $valueFloat);
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
                        [$intValue, $stringValue, $floatValue] = MysqlConverter::convertInputTypeToDatabaseTriplet($field, $field->default);
                    } else {
                        // TODO: delete edge property?
                        continue;
                    }
                } else {
                    [$intValue, $stringValue, $floatValue] = MysqlConverter::convertInputTypeToDatabaseTriplet($field, $data[$key]);
                }
                $this->insertOrUpdateEdgeProperty($parentId, $childId, $relation->name, $childName, $key, $intValue, $stringValue, $floatValue);
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
        if ($mode === 'REMOVE') {
            $ids = [];
            foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
                $ids[] = $row->id;
            }
            $this->deleteRelations($childIds, $ids);
        } else {
            foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
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
        Mysql::execute($sql, $params);
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
        Mysql::execute($sql, array_merge($params, $params2));
    }

    protected function insertNode(string $tenantId, string $id, string $name): bool
    {
        $sql = 'INSERT INTO `node` (`id`, `tenant_id`, `model`) VALUES (:id, :tenant_id, :model)';
        $params = [
            ':id'    => $id,
            ':tenant_id' => $tenantId,
            ':model' => $name
        ];
        return Mysql::execute($sql, $params) > 0;
    }

    protected function insertOrUpdateEdge(string $tenantId, string $parentId, string $childId, string $parent, string $child): bool
    {
        $sql = 'INSERT INTO `edge` (`parent_id`, `child_id`, `tenant_id`, `parent`, `child`) VALUES 
            (:parent_id, :child_id, :tenant_id, :parent, :child)
            ON DUPLICATE KEY UPDATE `deleted_at` = null';
        $params = [
            ':parent_id' => $parentId,
            ':child_id'  => $childId,
            ':tenant_id' => $tenantId,
            ':parent'    => $parent,
            ':child'     => $child,
        ];
        return Mysql::execute($sql, $params) > 0;
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
        $params = [
            ':node_id'       => $nodeId,
            ':model'         => $name,
            ':property'      => $propertyName,
            ':value_int'     => $valueInt,
            ':value_string'  => $valueString,
            ':value_float'   => $valueFloat,
            ':value_int2'    => $valueInt,
            ':value_string2' => $valueString,
            ':value_float2'   => $valueFloat
        ];
        return Mysql::execute($sql, $params) > 0;
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
        $params =             [
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
        ];
        return Mysql::execute($sql, $params) > 0;
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
        $params = [
            ':parent_id'       => $parentId,
            ':child_id'       => $childId,
            ':parent'         => $parent,
            ':child'         => $child,
            ':property'      => $propertyName,
        ];
        return Mysql::execute($sql, $params) > 0;
    }


    protected function updateNode(string $tenantId, string $id): int
    {
        // TODO: maybe add model-name to this?
        $sql = 'UPDATE `node` SET `updated_at` = now() WHERE id = :id';
        $params = [':id' => $id];
        if ($tenantId !== null) {
            $sql .= ' AND `tenant_id` = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }
        return Mysql::execute($sql, $params);
    }

    protected function deleteNodeProperty(string $nodeId, string $propertyName): bool
    {
        $sql = 'UPDATE `node_property` SET `deleted_at` = now() '
            . 'WHERE `deleted_at` IS NULL AND `node_id` = :node_id AND `property` = :property';
        $params = [
            ':node_id'  => $nodeId,
            ':property' => $propertyName,
        ];
        return Mysql::execute($sql, $params) > 0;
    }

    public function delete(string $tenantId, string $name, string $id): ?stdClass
    {
        $node = $this->load($tenantId, $name, $id);
        $this->deleteNode($tenantId, $id);
        $this->deleteEdgesForNodeId($tenantId, $id);
        $model = Model::get($name);
        $model->afterDelete($node); // TODO: nullpointer exception?
        return $node;
    }

    public function restore(?string $tenantId, string $name, string $id): stdClass
    {
        $this->restoreNode($tenantId, $id);
        $node = $this->load($tenantId, $name, $id);
        $model = Model::get($name);
        $model->afterUpdate($node);  // TODO: nullpointer exception?
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
        return Mysql::execute($sql, $params) > 0;
    }

    protected function deleteEdgesForNodeId(?string $tenantId, string $id): bool
    {
        $sql = 'UPDATE `edge` SET `deleted_at` = now() WHERE (`parent_id` = :id1 OR `child_id` = :id2)';
        $params = [':id1' => $id, ':id2' => $id];
        if ($tenantId !== null) {
            $sql .= ' AND `tenant_id` = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }
        return Mysql::execute($sql, $params) > 0;
    }

    protected function restoreNode(?string $tenantId, string $id): bool
    {
        $sql = 'UPDATE `node` SET `deleted_at` = NULL WHERE `id` = :id';
        $params = [':id' => $id];
        if ($tenantId !== null) {
            $sql .= ' AND `tenant_id` = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }
        return Mysql::execute($sql, $params) > 0;
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