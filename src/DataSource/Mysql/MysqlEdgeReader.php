<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Carbon\Carbon;
use Closure;
use GraphQL\Error\Error;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\Enums\ResultType;
use Mrap\GraphCool\Types\Objects\PaginatorInfoType;
use Mrap\GraphCool\Utils\StopWatch;
use Mrap\GraphCool\Utils\TimeZone;
use RuntimeException;
use stdClass;

class MysqlEdgeReader
{
    public function loadEdges(stdClass $node, string $name): stdClass
    {
        $model = Model::get($name);
        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $node->$key = $this->getClosure($node->id, $relation, $node->tenant_id);
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
        match ($resultType) {
            'ONLY_SOFT_DELETED' => $query->onlySoftDeleted(),
            'WITH_TRASHED' => $query->withTrashed(),
            default => null
        };

        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $edgeIds) {
            $edge = $this->fetchEdge($tenantId, $edgeIds->parent_id, $edgeIds->child_id, $resultType);
            if ($edge === null) {
                // this should never happen!
                throw new RuntimeException(
                    'Edge was null: ' . print_r(
                        [
                            'parent_id' => $edgeIds->parent_id,
                            'child_id' => $edgeIds->child_id,
                            'resultType' => $resultType
                        ],
                        true
                    )
                );
            }
            $properties = MysqlConverter::convertProperties(
                $this->fetchEdgeProperties($edge->parent_id, $edge->child_id),
                $relation
            );
            foreach ($properties as $key => $value) {
                $edge->$key = $value;
            }
            if ($relation->type === Relation::HAS_ONE || $relation->type === Relation::HAS_MANY) {
                $edge->_node = Mysql::nodeReader()->load($tenantId, $relation->name, $edge->child_id);
            } elseif ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::BELONGS_TO_MANY) {
                $edge->_node = Mysql::nodeReader()->load($tenantId, $relation->name, $edge->parent_id);
            }
            $edges[] = $edge;
        }

        $total = (int)Mysql::fetchColumn($query->toCountSql(), $query->getParameters());

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
            'WITH_TRASHED' => '',
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

    protected function fetchEdgeProperties(string $parentId, string $childId): array
    {
        $sql = 'SELECT * FROM `edge_property` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `deleted_at` IS NULL';
        $params = [
            ':parent_id' => $parentId,
            ':child_id' => $childId
        ];
        return Mysql::fetchAll($sql, $params);
    }


}