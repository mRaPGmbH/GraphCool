<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Carbon\Carbon;
use Closure;
use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\ResultType;
use Mrap\GraphCool\Types\Objects\PaginatorInfoType;
use Mrap\GraphCool\Utils\StopWatch;
use Mrap\GraphCool\Utils\TimeZone;
use RuntimeException;
use stdClass;
use function Mrap\GraphCool\model;

class MysqlEdgeReader
{
    public function loadEdges(stdClass $node, string $name): stdClass
    {
        $model = model($name);
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
        $resultType = $args['result'] ?? ResultType::DEFAULT;

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
        $total = (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());

        $idGroups = [];
        $nodeIds = [];
        $map = [];
        $edges = [];
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

        if (count($nodeIds) > 0) {
            $nodes = [];
            $closure = DB::findAll($tenantId, $relation->name, ['first' => 99999, 'where' => ['column' => 'id', 'operator' => 'IN', 'value' => $nodeIds]])->data;
            foreach($closure() as $node) {
                $nodes[$node->id] = $node;
            }
            foreach ($edges as $edge) {
                $edge->_node = $nodes[$edge->_node->id];
            }
        }

        StopWatch::stop(__METHOD__);
        if ($relation->type === Relation::HAS_MANY || $relation->type === Relation::BELONGS_TO_MANY) {
            $count = count($edges);
            return [
                'paginatorInfo' => PaginatorInfoType::create($count, $page, $limit, $total),
                'edges' => $edges,
            ];
        }
        return $edges;
    }

    protected function fetchEdge(
        ?string $tenantId,
        string $parentId,
        string $childId,
        ?string $resultType = ResultType::DEFAULT
    ): ?stdClass {
        $sql = 'SELECT * FROM `edge` WHERE `parent_id` = :parent_id AND `child_id` = :child_id ';
        $parameters = [
            ':parent_id' => $parentId,
            ':child_id' => $childId
        ];

        if ($tenantId !== null) {
            $sql .= 'AND `tenant_id` = :tenant_id ';
            $parameters[':tenant_id'] = $tenantId;
        }
        $sql .= match ($resultType) {
            'ONLY_SOFT_DELETED' => 'AND `deleted_at` IS NOT NULL ',
            'WITH_TRASHED', 'NONTRASHED_EDGES_OF_ANY_NODES' => '',
            default => 'AND `deleted_at` IS NULL ',
        };

        $edge = Mysql::fetch($sql, $parameters);
        if ($edge === null) {
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

    /**
     * @param string $parentId
     * @param string $childId
     * @return stdClass[]
     */
    protected function fetchEdgeProperties(string $parentId, string $childId): array
    {
        $sql = 'SELECT * FROM `edge_property` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `deleted_at` IS NULL';
        $params = [
            ':parent_id' => $parentId,
            ':child_id' => $childId
        ];
        return Mysql::fetchAll($sql, $params);
    }

    protected function fetchEdgePropertiesMulti(array $idGroups): array
    {
        if (count($idGroups) === 0) {
            return [];
        }
        $sqlParts = [];
        $params = [];
        $i = 0;
        foreach ($idGroups as $idGroup) {
            $sqlParts[] = '(`parent_id` = :p' . $i .' AND `child_id` = :c' . $i . ')';
            $params['p' . $i] = $idGroup->parent_id;
            $params['c' . $i] = $idGroup->child_id;
            $i++;
        }
        $sql = 'SELECT * FROM `edge_property` WHERE (' . implode(' OR ', $sqlParts) . ') AND `deleted_at` IS NULL';
        return Mysql::fetchAll($sql, $params);
    }


}