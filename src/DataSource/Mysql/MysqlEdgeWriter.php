<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;

class MysqlEdgeWriter
{
    public function writeEdges(string $tenantId, string $name, string $id, array $data): void
    {
        if (empty($id)) {
            return;
        }
        $model = Model::get($name);
        foreach ($model as $key => $item) {
            if (!$item instanceof Relation) {
                continue;
            }
            if (!array_key_exists($key, $data)) {
                continue;
            }
            if ($item->type !== Relation::BELONGS_TO && $item->type !== Relation::BELONGS_TO_MANY) {
                continue;
            }
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
        }
    }

    protected function deleteAllRelations(array $childIds, string $parentName): void
    {
        $params = [];
        $i = 1;
        foreach ($childIds as $childId) {
            $params[':child_id' . $i++] = $childId;
        }
        $sql = 'UPDATE `edge` SET `deleted_at` = now() WHERE `child_id` IN ';
        $sql .= '(' . implode(',', array_keys($params)) . ') ';
        $sql .= ' AND parent = :parent';
        $params[':parent'] = $parentName;
        Mysql::execute($sql, $params);
    }

    protected function insertOrUpdateBelongsRelation(
        string $tenantId,
        Relation $relation,
        array $data,
        string $parentId,
        array $childIds,
        string $childName
    ): void {
        foreach ($childIds as $childId) {
            $this->insertOrUpdateEdge($tenantId, $parentId, $childId, $relation->name, $childName);
            /** @var Field $field */
            foreach ($relation as $key => $field) {
                if (!isset($data[$key])) {
                    if (($field->default ?? null) !== null) {
                        [$intValue, $stringValue, $floatValue] = MysqlConverter::convertInputTypeToDatabaseTriplet(
                            $field,
                            $field->default
                        );
                    } else {
                        // TODO: delete edge property?
                        continue;
                    }
                } else {
                    [$intValue, $stringValue, $floatValue] = MysqlConverter::convertInputTypeToDatabaseTriplet(
                        $field,
                        $data[$key]
                    );
                }
                $this->insertOrUpdateEdgeProperty(
                    $parentId,
                    $childId,
                    $relation->name,
                    $childName,
                    $key,
                    $intValue,
                    $stringValue,
                    $floatValue
                );
            }
        }
    }

    protected function insertOrUpdateEdge(
        string $tenantId,
        string $parentId,
        string $childId,
        string $parent,
        string $child
    ): bool {
        $sql = 'INSERT INTO `edge` (`parent_id`, `child_id`, `tenant_id`, `parent`, `child`) VALUES 
            (:parent_id, :child_id, :tenant_id, :parent, :child)
            ON DUPLICATE KEY UPDATE `deleted_at` = null';
        $params = [
            ':parent_id' => $parentId,
            ':child_id' => $childId,
            ':tenant_id' => $tenantId,
            ':parent' => $parent,
            ':child' => $child,
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
    ): bool {
        $sql = 'INSERT INTO `edge_property` (`parent_id`, `child_id`, `parent`, `child`, `property`, `value_int`, `value_string`, `value_float`) '
            . 'VALUES (:parent_id, :child_id, :parent, :child, :property, :value_int, :value_string, :value_float) '
            . 'ON DUPLICATE KEY UPDATE `value_int` = :value_int2, `value_string` = :value_string2, `value_float` = :value_float2, `deleted_at` = NULL';
        $params = [
            ':parent_id' => $parentId,
            ':child_id' => $childId,
            ':parent' => $parent,
            ':child' => $child,
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

    protected function insertOrUpdateBelongsManyRelation(
        ?string $tenantId,
        Relation $relation,
        array $data,
        array $childIds,
        string $childName
    ): void {
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

    public function updateEdges(string $tenantId, string $name, array $ids, array $updates): void
    {
        if (count($ids) === 0) {
            return;
        }
        $model = Model::get($name);
        foreach ($model as $key => $item) {
            if (!array_key_exists($key, $updates)) {
                continue;
            }
            if ($item instanceof Relation && ($item->type === Relation::BELONGS_TO || $item->type === Relation::BELONGS_TO_MANY)) {
                $inputs = $updates[$key];
                if ($item->type === Relation::BELONGS_TO) {
                    $parentId = $inputs['id'];
                    unset($inputs['id']);
                    $this->deleteAllRelations($ids, $item->name);
                    $this->insertOrUpdateBelongsRelation($tenantId, $item, $inputs, $parentId, $ids, $name);
                } else {
                    foreach ($inputs as $input) {
                        $this->insertOrUpdateBelongsManyRelation($tenantId, $item, $input, $ids, $name);
                    }
                }
            }
        }
    }

}