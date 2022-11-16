<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Carbon\Carbon;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\ResultType;
use Mrap\GraphCool\Utils\StopWatch;
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
    ): ?stdClass {
        $nodes = $this->fetchNodes($tenantId, [$id], $name, $resultType);
        if ($nodes === null || count($nodes) === 0) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }
        $node = array_pop($nodes);
        if ($node->model !== $name) {
            throw new RuntimeException(
                'Class mismatch: expected "' . $name . '" but found "'
                . $node->model . '" instead (id:"' . $id . '")'
            );
        }
        $model = model($name);
        foreach ($this->fetchNodeProperties([$id]) as $property) {
            $key = $property->property;
            if (!property_exists($model, $key)) {
                continue;
            }
            $field = $model->$key;
            if ($field instanceof Relation) {
                continue;
            }
            $node->$key = MysqlConverter::convertDatabaseTypeToOutput($field, $property);
        }
        return Mysql::edgeReader()->loadEdges($node, $name);
    }

    public function loadMany(
        ?string $tenantId,
        string $name,
        array $ids,
        ?string $resultType = ResultType::DEFAULT
    ): array {
        StopWatch::start(__METHOD__);
        $nodes = $this->fetchNodes($tenantId, $ids, $name, $resultType);
        if ($nodes === null || count($nodes) === 0) {
            // @codeCoverageIgnoreStart
            return [];
            // @codeCoverageIgnoreEnd
        }
        $model = model($name);
        $map = [];
        foreach ($nodes as $key => $node) {
            if ($node->model !== $name) {
                throw new RuntimeException(
                    'Class mismatch: expected "' . $name . '" but found "'
                    . $node->model . '" instead (id:"' . $node->id . '")'
                );
            }
            $nodes[$key] = Mysql::edgeReader()->loadEdges($node, $name);
            $map[$node->id] = $key;
        }
        foreach ($this->fetchNodeProperties($ids) as $property) {
            $key = $property->property;
            if (!property_exists($model, $key)) {
                continue;
            }
            $field = $model->$key;
            if ($field instanceof Relation) {
                continue;
            }
            $i = $map[$property->node_id] ?? null;
            if ($i === null) {
                continue;
            }
            $nodes[$i]->$key = MysqlConverter::convertDatabaseTypeToOutput($field, $property);
        }
        StopWatch::stop(__METHOD__);
        return $nodes;
    }

    protected function fetchNodes(?string $tenantId, array $ids, string $name, ?string $resultType = ResultType::DEFAULT): ?array
    {
        StopWatch::start(__METHOD__);
        $model = model($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant($tenantId);

        $query->select(['*'])
            ->where(['column' => 'id', 'operator' => 'IN', 'value' => $ids]);
        match ($resultType) {
            'ONLY_SOFT_DELETED' => $query->onlySoftDeleted(),
            'WITH_TRASHED' => $query->withTrashed(),
            default => null
        };

        $dates = ['updated_at', 'created_at', 'deleted_at'];
        $sorted = array_flip($ids);
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $node) {
            foreach ($dates as $date) {
                if ($node->$date !== null) {
                    $dateTime = Carbon::parse($node->$date);
                    $node->$date = $dateTime->getPreciseTimestamp(3);
                }
            }
            $sorted[$node->id] = $node;
        }
        foreach ($sorted as $i => $node) {
            if (!$node instanceof stdClass) {
                unset($sorted[$i]);
            }
        }
        StopWatch::stop(__METHOD__);
        return array_values($sorted);
    }

    protected function fetchNodeProperties(array $ids): array
    {
        StopWatch::start(__METHOD__);
        $params = [];
        $i = 0;
        foreach ($ids as $id) {
            $p = ':p'.$i;
            $params[$p] = $id;
            $i++;
        }
        $sql = 'SELECT * FROM `node_property` WHERE `node_id` IN (' . implode(',', array_keys($params)) . ') AND `deleted_at` IS NULL';
        $result = Mysql::fetchAll($sql, $params);
        StopWatch::stop(__METHOD__);
        return $result;
    }

}
