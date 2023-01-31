<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Carbon\Carbon;
use Mrap\GraphCool\Types\Enums\Result;
use Mrap\GraphCool\Utils\StopWatch;
use stdClass;
use function Mrap\GraphCool\model;

class MysqlNodeReader
{

    /** @var string[] */
    protected array $nodeIds = [];

    /** @var stdClass[] */
    protected array $loadedNodes = [];

    public function load(
        string $name,
        string $id,
        ?string $resultType = Result::DEFAULT
    ): ?stdClass {
        $this->addNode($id);
        $this->loadNodes();
        return $this->filterNode($id, $name, $resultType);
    }

    public function loadMany(
        string $name,
        array $ids,
        ?string $resultType = Result::DEFAULT
    ): array {
        StopWatch::start(__METHOD__);
        foreach ($ids as $id) {
            $this->addNode($id);
        }
        $this->loadNodes();
        return $this->filterNodes($ids, $name, $resultType);
    }

    protected function addNode(string $id): void
    {
        if (!array_key_exists($id, $this->loadedNodes)) {
            $this->nodeIds[$id] = $id;
        }
    }

    protected function loadNodes(): void
    {
        if (count($this->nodeIds) === 0) {
            return;
        }
        StopWatch::start(__METHOD__);
        $nodes = [];
        foreach ($this->fetchNodes($this->nodeIds) as $id => $node) {
            $nodes[$id] = Mysql::edgeReader()->loadEdges($node, $node->model);
        }
        foreach ($this->fetchNodeProperties($this->nodeIds) as $property) {
            $key = $property->property;
            $field = model($property->model)->$key;
            $nodes[$property->node_id]->$key = MysqlConverter::convertDatabaseTypeToOutput($field, $property);
        }
        $this->loadedNodes = array_merge($this->loadedNodes, $nodes);
        $this->nodeIds = [];
        StopWatch::stop(__METHOD__);
    }

    protected function filterNode(string $id, string $name, ?string $resultType = Result::DEFAULT): ?stdClass
    {
        $array = $this->filterNodes([$id], $name, $resultType);
        return array_pop($array);
    }

    protected function filterNodes(array $ids, string $name, ?string $resultType = Result::DEFAULT): array
    {
        $result = [];
        foreach ($ids as $id) {
            $node = $this->loadedNodes[$id] ?? null;
            if ($this->nodeMatches($node, $name, $resultType)) {
                $result[$id] = $node;
            }
        }
        return $result;
    }

    protected function nodeMatches(?stdClass $node, string $name, string $resultType): bool
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

    protected function fetchNodes(array $ids): ?array
    {
        StopWatch::start(__METHOD__);
        $query = MysqlQueryBuilder::forModel(null, null)
            ->tenant(Mysql::tenantId())
            ->select(['*'])
            ->where(['column' => 'id', 'operator' => 'IN', 'value' => $ids])
            ->withTrashed();
        $nodes = [];
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $node) {
            foreach (['updated_at', 'created_at', 'deleted_at'] as $date) {
                if ($node->$date !== null) {
                    $dateTime = Carbon::parse($node->$date);
                    $node->$date = $dateTime->getPreciseTimestamp(3);
                }
            }
            $nodes[$node->id] = Mysql::edgeReader()->loadEdges($node, 'sdf');
        }
        StopWatch::stop(__METHOD__);
        return $nodes;
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
