<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Closure;
use GraphQL\Error\Error;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Types\Type;
use RuntimeException;
use stdClass;
use function Mrap\GraphCool\model;

class MysqlNodeWriter
{
    /**
     * @param string $tenantId
     * @param string $name
     * @param string $id
     * @param mixed[] $data
     */
    public function insert(string $tenantId, string $name, string $id, array $data): void
    {
        $this->insertNode($tenantId, $id, $name);
        $model = model($name);
        foreach (get_object_vars($model) as $key => $item) {
            if (!$item instanceof Field) {
                continue;
            }
            if (in_array($item->type, [Type::ID, Field::UPDATED_AT, Field::DELETED_AT, Field::CREATED_AT], true)) {
                continue;
            }
            if (!array_key_exists($key, $data) && $item->null === true) {
                continue;
            }
            $value = $data[$key] ?? $item->default ?? null;
            if ($value instanceof Closure) {
                $value = $value();
            }
            if ($value === '') {
                $value = null;
            }
            if ($value === null && $item->null === false) {
                throw new RuntimeException('Field ' . $name . '.' . $key . ' may not be null.');
            }
            $this->insertOrUpdateModelField($item, $value, $id, $name, $key);
        }
        Mysql::edgeWriter()->writeEdges($tenantId, $name, $id, $data);
    }

    protected function insertNode(string $tenantId, string $id, string $name): bool
    {
        $sql = 'INSERT INTO `node` (`id`, `tenant_id`, `model`) VALUES (:id, :tenant_id, :model)';
        $params = [
            ':id' => $id,
            ':tenant_id' => $tenantId,
            ':model' => $name
        ];
        return Mysql::execute($sql, $params) > 0;
    }

    protected function insertOrUpdateModelField(Field $field, mixed $value, string $id, string $name, string $key): void
    {
        if ($value === null && $field->null === true) {
            $this->deleteNodeProperty($id, $key);
            return;
        }
        [$valueInt, $valueString, $valueFloat] = MysqlConverter::convertInputTypeToDatabaseTriplet($field, $value);
        $this->insertOrUpdateNodeProperty($id, $name, $key, $valueInt, $valueString, $valueFloat);
    }

    protected function deleteNodeProperty(string $nodeId, string $propertyName): bool
    {
        $sql = 'UPDATE `node_property` SET `deleted_at` = now() '
            . 'WHERE `deleted_at` IS NULL AND `node_id` = :node_id AND `property` = :property';
        $params = [
            ':node_id' => $nodeId,
            ':property' => $propertyName,
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
            ':node_id' => $nodeId,
            ':model' => $name,
            ':property' => $propertyName,
            ':value_int' => $valueInt,
            ':value_string' => $valueString,
            ':value_float' => $valueFloat,
            ':value_int2' => $valueInt,
            ':value_string2' => $valueString,
            ':value_float2' => $valueFloat
        ];
        return Mysql::execute($sql, $params) > 0;
    }

    /**
     * @param string $tenantId
     * @param string $name
     * @param string $id
     * @param mixed[] $updates
     */
    public function update(string $tenantId, string $name, string $id, array $updates): void
    {
        $model = model($name);
        Mysql::edgeWriter()->updateEdges($tenantId, $name, [$id], $updates);

        $updates = $model->afterRelationUpdateButBeforeNodeUpdate($tenantId, $id, $updates);

        foreach (get_object_vars($model) as $key => $item) {
            if (!array_key_exists($key, $updates)) {
                continue;
            }
            if ($item instanceof Field) {
                $this->insertOrUpdateModelField($item, $updates[$key], $id, $name, $key);
            }
        }
        $this->updateNode($tenantId, $id);
    }

    /**
     * @param string $tenantId
     * @param string $name
     * @param string[] $ids
     * @param mixed[] $updateData
     * @return stdClass
     * @throws Error
     */
    public function updateMany(string $tenantId, string $name, array $ids, array $updateData): stdClass
    {
        $model = model($name);
        $updates = [
            'deleted_at' => null
        ];
        foreach (get_object_vars($model) as $key => $item) {
            if (!array_key_exists($key, $updateData)) {
                continue;
            }
            if (!$item instanceof Field) {
                continue;
            }
            if ($item->unique === true && $updateData[$key] !== null) {
                throw new Error(
                    'Field ' . $key . ' is defined as unique cannot be set to the same value for many items. Use `update` instead of `updateMany`'
                );
            }
            $updates[$key] = $updateData[$key];
        }

        // TODO: $updates = $model->beforeUpdate($updates); <- beforeUpdate has to be free of individual (single-entity) stuff
        Mysql::edgeWriter()->updateEdges($tenantId, $name, $ids, $updateData);

        $query = MysqlQueryBuilder::forModel($model, $name)
            ->tenant($tenantId)
            ->update($updates)
            ->where($data['where'] ?? null)
            ->withTrashed(); // TODO: should this depend on $resultType?

        $result = new stdClass();
        $result->updated_rows = (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());
        Mysql::execute($query->toSql(), $query->getUpdateParameters());

        return $result;
    }

    public function delete(string $tenantId, string $id): void
    {
        $this->deleteNode($tenantId, $id);
        $this->deleteEdgesForNodeId($tenantId, $id);
    }

    public function deleteMany(string $tenantId, array $ids): void
    {
        $this->deleteNodes($tenantId, $ids);
        foreach ($ids as $id) {
            $this->deleteEdgesForNodeId($tenantId, $id);
        }
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

    protected function deleteNodes(?string $tenantId, array $ids): bool
    {
        if (count($ids) === 0) {
            return true;
        }
        $params = [];
        $sqlParts = [];
        foreach (array_values($ids) as $i => $id) {
            $sqlParts[] = ':id' . $i;
            $params[':id' . $i] = $id;
        }
        $sql = 'UPDATE `node` SET `deleted_at` = now() WHERE `id` IN (' . implode(',', $sqlParts) . ')';
        if ($tenantId !== null) {
            $sql .= ' AND `tenant_id` = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }
        return Mysql::execute($sql, $params) > 0;
    }

    protected function updateNode(?string $tenantId, string $id): bool
    {
        $sql = 'UPDATE `node` SET `updated_at` = now() WHERE `id` = :id';
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

    public function restore(string $tenantId, string $id): void
    {
        $this->restoreNode($tenantId, $id);
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

}
