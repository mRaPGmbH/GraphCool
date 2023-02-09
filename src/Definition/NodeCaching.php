<?php

namespace Mrap\GraphCool\Definition;

use GraphQL\Deferred;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Types\Enums\Result;
use RuntimeException;
use stdClass;

trait NodeCaching
{

    private static ?string $tenantId = null;

    /** @var string[] */
    private static array $nodeIds = [];

    /** @var stdClass[] */
    private static array $loadedNodes = [];

    /** @var stdClass[] */
    private static array $edgeIds = [];

    /** @var stdClass[] */
    private static array $loadedEdges = [];


    protected function loadNodeDeferred(string $tenantId, string $name, string $id, ?string $resultType = Result::DEFAULT): Deferred
    {
        $this->checkTenantId($tenantId);
        $this->addNodeId($id);
        return new Deferred(function () use ($name, $id, $resultType) {
            $this->loadNodes();
            return $this->filterNode($id, $name, $resultType);
        });
    }

    protected function loadNodesDeferred(string $tenantId, string $name, array $ids, ?string $resultType = Result::DEFAULT): Deferred
    {
        $this->checkTenantId($tenantId);
        foreach ($ids as $id) {
            $this->addNodeId($id);
        }
        return new Deferred(function () use ($name, $ids, $resultType) {
            $this->loadNodes();
            return $this->filterNodes($ids, $name, $resultType);
        });
    }

    protected function findNodesDeferred(?string $tenantId, string $name, array $args): Deferred
    {
        $this->checkTenantId($tenantId);
        $all = DB::findNodes($tenantId, $name, $args);
        $resultType = $args['result'] ?? Result::DEFAULT;
        foreach ($all->ids as $id) {
            $this->addNodeId($id);
        }
        return new Deferred(function () use ($name, $all, $resultType) {
            $this->loadNodes();
            $ids = $all->ids;
            $all->data = function() use ($name, $ids, $resultType) {
                return $this->filterNodes($ids, $name, $resultType);
            };
            return $all;
        });
    }

    private function findEdgesDeferred(?string $tenantId, stdClass $modelData, Relation $relation, array $args): Deferred
    {
        $this->checkTenantId($tenantId, 'Edge');
        $result = DB::findEdges($tenantId, $modelData->id, $relation, $args);
        if (is_array($result)) {
            $edgeIds = $result;
        } else {
            $edgeIds = $result->edges;
        }
        foreach ($edgeIds as $edgeId) {
            $this->addEdgeId($edgeId);
            $this->addNodeId($edgeId->parent_id);
            $this->addNodeId($edgeId->child_id);
        }
        return new Deferred(function () use ($result, $edgeIds, $relation) {
            $this->loadNodes();
            $this->loadEdges();
            if (is_array($result)) {
                return $this->filterEdges($edgeIds, $relation)[0] ?? null;
            }
            $result->edges = $this->filterEdges($edgeIds, $relation);
            return $result;
        });
    }


    private function addNodeId(string $id): void
    {
        if (!array_key_exists($id, self::$loadedNodes)) {
            self::$nodeIds[$id] = $id;
        }
    }

    private function combineEdgeId(stdClass $edge): string
    {
        return $edge->parent_id . '.' . $edge->child_id;
    }

    private function addEdgeId(stdClass $edgeId): void
    {
        $combined = $this->combineEdgeId($edgeId);
        if (!array_key_exists($combined, self::$loadedEdges)) {
            self::$edgeIds[$combined] = $edgeId;
        }
    }

    private function loadNodes(): void
    {
        if (count(static::$nodeIds) === 0) {
            return;
        }
        static::$loadedNodes = array_merge(self::$loadedNodes, DB::loadNodes(self::$tenantId, self::$nodeIds));
        static::$nodeIds = [];
    }

    private function filterNode(string $id, string $name, ?string $resultType = Result::DEFAULT): ?stdClass
    {
        $array = $this->filterNodes([$id], $name, $resultType);
        return array_pop($array);
    }

    private function filterNodes(array $ids, string $name, ?string $resultType = Result::DEFAULT): array
    {
        $result = [];
        foreach ($ids as $id) {
            $node = self::$loadedNodes[$id] ?? null;
            if ($this->nodeMatches($node, $name, $resultType)) {
                $result[$id] = $node;
            }
        }
        return $result;
    }

    private function nodeMatches(?stdClass $node, string $name, string $resultType): bool
    {
        if ($node === null) {
            return false;
        }
        if ($node->model !== $name) {
            return false;
        }
        if ($resultType === Result::DEFAULT && $node->deleted_at !== null) {
            return false;
        }
        if ($resultType === Result::ONLY_SOFT_DELETED && $node->deleted_at === null) {
            return false;
        }
        return true;
    }

    private function checkTenantId(string $tenantId, string $errorText = 'Node'): void
    {
        if (self::$tenantId !== null && static::$tenantId !== $tenantId) {
            throw new RuntimeException('Cannot change TenantId when using ' . $errorText . 'Caching: ' . static::$tenantId . ' => ' . $tenantId);
        }
        self::$tenantId = $tenantId;
    }

    private function loadEdges(): void
    {
        if (count(static::$edgeIds) === 0) {
            return;
        }
        static::$loadedEdges = array_merge(self::$loadedEdges, DB::loadEdges(self::$tenantId, self::$edgeIds));
        static::$edgeIds = [];
    }

    private function filterEdges(array $edgeIds, Relation $relation): array
    {
        $result = [];
        foreach ($edgeIds as $edgeId) {
            $combined = $this->combineEdgeId($edgeId);
            $edge = self::$loadedEdges[$combined] ?? null;
            if ($edge !== null) {
                $edgeClone = clone $edge;
                $edgeClone->_node = match($relation->type) {
                    Relation::BELONGS_TO, Relation::BELONGS_TO_MANY => $this->filterNode($edge->parent_id, $edge->parent),
                    Relation::HAS_ONE, Relation::HAS_MANY => $this->filterNode($edge->child_id, $edge->child),
                };
                $result[] = $edgeClone;
            }
        }
        return $result;
    }


}