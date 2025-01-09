<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Carbon\Carbon;
use Closure;
use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\Result;
use Mrap\GraphCool\Types\Objects\PaginatorInfo;
use Mrap\GraphCool\Utils\StopWatch;
use Mrap\GraphCool\Utils\TimeZone;
use stdClass;
use function Mrap\GraphCool\model;

class MysqlEdgeReader
{
    public function injectEdgeClosures(stdClass $node): stdClass
    {
        $model = model($node->model);
        foreach (get_object_vars($model) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $node->$key = $this->getClosure($node->id, $relation, (string)$node->tenant_id);
        }
        return $node;
    }

    protected function getClosure(string $id, Relation $relation, string $tenantId): Closure
    {
        return function (array $args) use ($id, $relation, $tenantId) {
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

    public function findEdges(string $tenantId, string $nodeId, Relation $relation, array $args): array|stdClass
    {
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        if ($page < 1) {
            throw new Error('Page cannot be less than 1');
        }
        $offset = ($page - 1) * $limit;
        $whereNode = $args['where'] ?? null;
        $whereEdge = $args['whereEdge'] ?? null;
        $search = $args['search'] ?? null;
        $searchLoosely = $args['searchLoosely'] ?? null;
        $orderBy = $args['orderBy'] ?? [];
        $resultType = $args['result'] ?? Result::DEFAULT;

        $query = MysqlQueryBuilder::forRelation($relation, [$nodeId]);
        $query
            ->tenant($tenantId)
            ->select(['_parent_id', '_child_id', '_child', '_parent', '_updated_at', '_created_at', '_deleted_at'])
            ->limit($limit, $offset)
            ->where($whereEdge)
            ->whereRelated($whereNode)
            ->orderBy($orderBy)
            ->search($search)
            ->searchLoosely($searchLoosely);
        match ($resultType) {
            Result::ONLY_SOFT_DELETED => $query->onlySoftDeleted(),
            Result::WITH_TRASHED => $query->withTrashed(),
            'NONTRASHED_EDGES_OF_ANY_NODES' => $query->nontrashedEdgesOfAnyNodes(),
            default => null
        };
        $total = (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());

        $edges = Mysql::fetchAll($query->toSql(), $query->getParameters());
        foreach ($edges as $edge) {
            foreach (['updated_at', 'created_at', 'deleted_at'] as $date) {
                $edge->$date = Carbon::parse($edge->$date)->setTimezone(TimeZone::get())->format('Y-m-d\TH:i:s.vp');
            }
            $edge->__relation = $relation; // workaround to be able to get pivot-property fields later
        }

        if ($relation->type === Relation::HAS_MANY || $relation->type === Relation::BELONGS_TO_MANY) {
            $count = count($edges);
            return (object)[
                'paginatorInfo' => PaginatorInfo::create($count, $page, $limit, $total),
                'edges' => $edges,
            ];
        }
        return $edges;
    }

    public function loadEdges(array $edgeIds): array
    {
        foreach ($this->fetchEdgePropertiesMulti($edgeIds) as $property) {
            $combined = $property->parent_id . '.' . $property->child_id;
            $props = MysqlConverter::convertProperties([$property], $edgeIds[$combined]->__relation);
            foreach ($props as $key => $value) {
                $edgeIds[$combined]->$key = $value;
            }
        }
        return $edgeIds;
    }

    /**
     * @param string|null $tenantId
     * @param string $id
     * @param Relation $relation
     * @param mixed[] $args
     * @return mixed[]|stdClass
     * @throws Error
     */
    protected function findRelatedNodes(?string $tenantId, string $id, Relation $relation, array $args): array|stdClass
    {
        StopWatch::start(__METHOD__);
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        if ($page < 1) {
            throw new Error('Page cannot be less than 1');
        }
        $offset = ($page - 1) * $limit;
        $whereNode = $args['where'] ?? null;
        $whereEdge = $args['whereEdge'] ?? null;
        $search = $args['search'] ?? null;
        $searchLoosely = $args['searchLoosely'] ?? null;
        $orderBy = $args['orderBy'] ?? [];
        $resultType = $args['result'] ?? Result::DEFAULT;

        $query = MysqlQueryBuilder::forRelation($relation, [$id]);
        $query
            ->tenant($tenantId)
            ->select(['*'])
            ->limit($limit, $offset)
            ->where($whereEdge)
            ->whereRelated($whereNode)
            ->orderBy($orderBy)
            ->search($search)
            ->searchLoosely($searchLoosely);
        match ($resultType) {
            'ONLY_SOFT_DELETED' => $query->onlySoftDeleted(),
            'WITH_TRASHED' => $query->withTrashed(),
            'NONTRASHED_EDGES_OF_ANY_NODES' => $query->nontrashedEdgesOfAnyNodes(),
            default => null
        };
        //throw new Error($query->toSql());
        StopWatch::start('related count');
        $total = (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());
        StopWatch::stop('related count');

        $idGroups = [];
        $nodeIds = [];
        $map = [];
        $edges = [];
        StopWatch::start('related fetchAll');
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
            $edge = new stdClass();
            $edge->child_id = $row->_child_id;
            $edge->child = $row->_child;
            $edge->parent_id = $row->_parent_id;
            $edge->parent = $row->_parent;
            $edge->_node = new stdClass();
            $edge->_node->id = $row->id;
            $edge->_node->model = $row->model;

            $nodeIds[] = $row->id;

            $dates = ['updated_at', 'created_at', 'deleted_at'];
            foreach ($dates as $date) {
                if ($row->$date !== null) {
                    $dateTime = Carbon::parse($row->$date);
                    $dateTime->setTimezone(TimeZone::get());
                    $edge->_node->$date = $dateTime->format('Y-m-d\TH:i:s.vp');
                }
                $date2 = '_' . $date;
                if ($row->$date2 !== null) {
                    $dateTime = Carbon::parse($row->$date2);
                    $dateTime->setTimezone(TimeZone::get());
                    $edge->$date = $dateTime->format('Y-m-d\TH:i:s.vp');
                }
            }
            $idGroups[] = (object)[
                'parent_id' => $edge->parent_id,
                'child_id' => $edge->child_id,
            ];
            $map[$edge->parent_id.'.'.$edge->child_id] = count($edges);
            $edges[] = $edge;
        }
        StopWatch::stop('related fetchAll');

        StopWatch::start('related edgeProperties');
        foreach ($this->fetchEdgePropertiesMulti($idGroups) as $property) {
            $props = MysqlConverter::convertProperties(
                [$property],
                $relation
            );

            $i = $map[$property->parent_id.'.'.$property->child_id];
            $edge = $edges[$i];

            foreach ($props as $key => $value) {
                $edge->$key = $value;
            }
        }
        StopWatch::stop('related edgeProperties');

        StopWatch::start('related loadNodes');
        if (count($nodeIds) > 0) {
            $nodes = [];
            $closure = DB::findNodes($tenantId, $relation->name, ['first' => 99999, 'where' => ['column' => 'id', 'operator' => 'IN', 'value' => $nodeIds]])->data;
            foreach($closure() as $node) {
                $nodes[$node->id] = $node;
            }
            foreach ($edges as $edge) {
                $edge->_node = $nodes[$edge->_node->id];
            }
        }
        StopWatch::stop('related loadNodes');

        StopWatch::stop(__METHOD__);
        if ($relation->type === Relation::HAS_MANY || $relation->type === Relation::BELONGS_TO_MANY) {
            $count = count($edges);
            return [
                'paginatorInfo' => PaginatorInfo::create($count, $page, $limit, $total),
                'edges' => $edges,
            ];
        }
        return $edges;
    }

    protected function fetchEdgePropertiesMulti(array $idGroups): array
    {
        StopWatch::start(__METHOD__);
        if (count($idGroups) === 0) {
            return [];
        }
        $sqlParts = [];
        $params = [];
        $i = 0;
        foreach ($idGroups as $idGroup) {
            $sqlParts[] = '(:p' . $i .', :c' . $i . ')';
            $params['p' . $i] = $idGroup->parent_id;
            $params['c' . $i] = $idGroup->child_id;
            $i++;
        }
        $sql = 'SELECT * FROM `edge_property` WHERE (`parent_id`, `child_id`) IN (' . implode(', ', $sqlParts) . ') AND `deleted_at` IS NULL';
        $result = Mysql::fetchAll($sql, $params);
        StopWatch::stop(__METHOD__);
        return $result;
    }


}
