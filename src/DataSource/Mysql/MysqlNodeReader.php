<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Carbon\Carbon;
use Mrap\GraphCool\Definition\Entity;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\ResultType;
use RuntimeException;
use stdClass;
use function Mrap\GraphCool\model;

class MysqlNodeReader
{
    public function load(
        ?string $tenantId,
        string $name,
        string $id,
        ?string $resultType = ResultType::DEFAULT
    ): ?Entity {
        $node = $this->fetchNode($tenantId, $id, $name, $resultType);
        if ($node === null) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }
        if ($node->model !== $name) {
            throw new RuntimeException(
                'Class mismatch: expected "' . $name . '" but found "'
                . $node->model . '" instead (id:"' . $id . '")'
            );
        }
        $model = model($name);
        foreach ($this->fetchNodeProperties($id) as $property) {
            $key = $property->property;
            if (!property_exists($model, $key)) {
                continue;
            }
            $field = $model->$key;
            if ($field instanceof Relation) {
                continue;
            }
            $node->$key = MysqlConverter::convertDatabaseTypeToOutput($field, $property, $name . '.' . $id . '.' . $key);
        }
        return Mysql::edgeReader()->loadEdges($node, $name);
    }

    protected function fetchNode(?string $tenantId, string $id, string $name, ?string $resultType = ResultType::DEFAULT): ?Entity
    {
        // TODO: use queryBuilder
        $sql = 'SELECT * FROM `node` WHERE `id` = :id AND `model` = :model ';
        $params = [':id' => $id, ':model' => $name];
        if ($tenantId !== null) {
            $sql .= 'AND `node`.`tenant_id` = :tenant_id ';
            $params[':tenant_id'] = $tenantId;
        }
        $sql .= match ($resultType) {
            'ONLY_SOFT_DELETED' => 'AND `deleted_at` IS NOT NULL ',
            'WITH_TRASHED' => '',
            default => 'AND `deleted_at` IS NULL ',
        };

        $node = Mysql::fetch($sql, $params, $name);
        if ($node === null) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
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

    /**
     * @param string $id
     * @return stdClass[]
     */
    protected function fetchNodeProperties(string $id): array
    {
        $sql = 'SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL';
        return Mysql::fetchAll($sql, [':node_id' => $id]);
    }


}