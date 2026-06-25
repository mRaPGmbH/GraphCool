<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Carbon\Carbon;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Types\Enums\Result;
use Mrap\GraphCool\Utils\Sorter;
use Mrap\GraphCool\Utils\StopWatch;
use stdClass;
use function Mrap\GraphCool\model;

class MysqlNodeReader
{
    // Reserved key holding a node's true entity type (the `node.model` column),
    // preserved so a model field literally named `model` cannot shadow it.
    public const ENTITY_TYPE_KEY = '__graphcool_entity_type';

    /**
     * The node's true entity type. A loaded node's `model` property may have been
     * overwritten by a model field literally named `model` (e.g. the file service's
     * File model), so always resolve the type through the reserved key, falling back
     * to `model` for nodes not produced by loadMany() (e.g. raw edge rows).
     */
    public static function entityType(stdClass $node): string
    {
        return $node->{self::ENTITY_TYPE_KEY} ?? $node->model;
    }

    /** @var string[] */
    protected array $nodeIds = [];

    /** @var stdClass[] */
    protected array $loadedNodes = [];

    public function load(?string $tenantId, string $id, ?string $resultType = Result::DEFAULT): ?stdClass {
        $result = $this->loadMany($tenantId, [$id], $resultType);
        return array_pop($result);
    }

    public function loadMany(?string $tenantId, array $ids, ?string $resultType = Result::DEFAULT): array {
        StopWatch::start(__METHOD__);
        if (count($ids) === 0) {
            StopWatch::stop(__METHOD__);
            return [];
        }
        $nodes = [];
        foreach ($this->fetchNodes($tenantId, $ids, $resultType) as $id => $node) {
            // Preserve the true entity type before node properties (which may include
            // a field literally named `model`) overwrite $node->model below.
            $node->{self::ENTITY_TYPE_KEY} = $node->model;
            $nodes[$id] = Mysql::edgeReader()->injectEdgeClosures($node);
        }
        if (count($nodes) === 0) {
            StopWatch::stop(__METHOD__);
            return [];
        }
        foreach ($this->fetchNodeProperties($ids) as $property) {
            $key = $property->property;
            $field = model($property->model)->$key ?? null;
            if (!($field instanceOf Field))  {
                continue;
            }
            $nodes[$property->node_id]->$key = MysqlConverter::convertDatabaseTypeToOutput($field, $property);
        }
        StopWatch::stop(__METHOD__);
        return $nodes;
    }

    protected function fetchNodes(?string $tenantId, array $ids, ?string $resultType = Result::DEFAULT): ?array
    {
        StopWatch::start(__METHOD__);
        $query = MysqlQueryBuilder::forModel(null, null)
            ->tenant($tenantId)
            ->select(['*'])
            ->where(['column' => 'id', 'operator' => 'IN', 'value' => $ids]);
        match ($resultType) {
            'ONLY_SOFT_DELETED' => $query->onlySoftDeleted(),
            'WITH_TRASHED' => $query->withTrashed(),
            default => null
        };
        $nodes = [];
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $node) {
            foreach (['updated_at', 'created_at', 'deleted_at'] as $date) {
                if ($node->$date !== null) {
                    $dateTime = Carbon::parse($node->$date);
                    $node->$date = $dateTime->getPreciseTimestamp(3);
                }
            }
            $nodes[$node->id] = Mysql::edgeReader()->injectEdgeClosures($node);
        }
        StopWatch::stop(__METHOD__);
        return Sorter::sortNodesByIdOrder($nodes, $ids);
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
