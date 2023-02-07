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

    protected function load(string $tenantId, string $name, string $id, ?string $resultType = Result::DEFAULT): Deferred
    {
        $this->checkTenantId($tenantId);
        $this->addId($id);
        return new Deferred(function () use ($name, $id, $resultType) {
            $this->loadNodes();
            return $this->filterNode($id, $name, $resultType);
        });
    }

    protected function loadAll(string $tenantId, string $name, array $ids, ?string $resultType = Result::DEFAULT): Deferred
    {
        $this->checkTenantId($tenantId);
        foreach ($ids as $id) {
            $this->addId($id);
        }
        return new Deferred(function () use ($name, $ids, $resultType) {
            $this->loadNodes();
            return $this->filterNodes($ids, $name, $resultType);
        });
    }

    protected function findAll(?string $tenantId, string $name, array $args): Deferred
    {
        $this->checkTenantId($tenantId);
        return new Deferred(function () use ($tenantId, $name, $args) {
            return DB::findAll($tenantId, $name, $args);
        });
    }

    private function addId(string $id): void
    {
        if (!array_key_exists($id, self::$loadedNodes)) {
            self::$nodeIds[$id] = $id;
        }
    }

    private function loadNodes(): void
    {
        if (count(static::$nodeIds) === 0) {
            return;
        }
        static::$loadedNodes = array_merge(self::$loadedNodes, DB::loadAll(self::$tenantId, self::$nodeIds));
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

    private function checkTenantId(string $tenantId): void
    {
        if (static::$tenantId !== null && static::$tenantId !== $tenantId) {
            throw new RuntimeException('Cannot change TenantId when using NodeCaching: ' . static::$tenantId . ' => ' . $tenantId);
        }
        static::$tenantId = $tenantId;
    }


}