<?php

namespace Mrap\GraphCool\DataSource\Mysql;

use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Utils\ClientInfo;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\StopWatch;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use stdClass;

class MysqlHistory
{

    protected array $preceeding = [];

    public function recordUpdate(?stdClass $oldNode, stdClass $newNode, array $properties): void
    {
        StopWatch::start(__METHOD__);
        $changes = $this->compare($oldNode, $newNode, $properties);
        $this->insert($newNode->tenant_id, $newNode->id, $newNode->model, 'update', $changes);
        StopWatch::stop(__METHOD__);
    }

    public function recordMassUpdate(string $tenantId, array $ids, string $model, array $changes): void
    {
        StopWatch::start(__METHOD__);
        foreach ($ids as $id) {
            $this->insert($tenantId, $id, $model, 'massUpdate', $changes);
        }
        StopWatch::stop(__METHOD__);
    }

    public function recordDelete(stdClass $node, array $properties): void
    {
        StopWatch::start(__METHOD__);
        $changes = $this->node($node, $properties);
        $this->insert($node->tenant_id, $node->id, $node->model, 'delete', $changes);
        StopWatch::stop(__METHOD__);
    }

    public function recordRestore(stdClass $node, array $properties): void
    {
        StopWatch::start(__METHOD__);
        $changes = $this->node($node, $properties);
        $this->insert($node->tenant_id, $node->id, $node->model, 'restore', $changes);
        StopWatch::stop(__METHOD__);
    }

    public function recordCreate(stdClass $node, array $properties): void
    {
        StopWatch::start(__METHOD__);
        $changes = $this->node($node, $properties);
        $this->insert($node->tenant_id, $node->id, $node->model, 'create', $changes);
        StopWatch::stop(__METHOD__);
    }

    protected function insert(string $tenantId, string $nodeId, string $model, string $type, array $changes): void
    {
        if (count($changes) === 0) {
            return;
        }
        $preceeding = $this->getPreceeding($tenantId);

        $sql = 'INSERT INTO `history` (`id`, `tenant_id`, `number`, `node_id`, `model`, `sub`, `ip`, `user_agent`, `change_type`, `changes`, `preceding_hash`, `hash`, `created_at`) '
            . 'VALUES (:id, :tenant_id, :number, :node_id, :model, :sub, :ip, :user_agent, :change_type, :changes, :preceding_hash, :hash, :created_at)';
        $params = [
            'id' => Uuid::uuid4()->toString(),
            'tenant_id' => $tenantId,
            'number' => ($preceeding->number ?? 0) + 1,
            'node_id' => $nodeId,
            'model' => $model,
            'sub' => JwtAuthentication::getClaim('sub'),
            'ip' => ClientInfo::ip(),
            'user_agent' => ClientInfo::user_agent(),
            'change_type' => $type,
            'changes' => json_encode($changes, JSON_THROW_ON_ERROR),
            'preceding_hash' => $preceeding->hash ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $params['hash'] = $this->hash($params);
        Mysql::execute($sql, $params);
        $this->preceeding[$tenantId] = (object)[
            'hash' => $params['hash'],
            'number' => $params['number']
        ];
    }

    protected function getPreceeding(string $tenantId): stdClass
    {
        if (!isset($this->preceeding[$tenantId])) {
            $sql = 'SELECT `number`, `hash` FROM `history` WHERE `tenant_id` = :tenant_id ORDER BY `number` DESC LIMIT 1';
            $params = ['tenant_id' => $tenantId];
            $this->preceeding[$tenantId] = Mysql::fetch($sql, $params) ?? (object)['number' => 0, 'hash' => null];
        }
        return $this->preceeding[$tenantId];
    }

    protected function compare(?stdClass $oldNode, stdClass $newNode, array $properties): array
    {
        $diff = [];
        foreach ($properties as $property => $subProperties) {
            if (is_array($subProperties)) {
                $closure = $newNode->$property;
                $subDiff = $this->compareEdge($oldNode->$property ?? [], $closure([])['edges'] ?? [], array_keys($subProperties));
                if (count($subDiff) > 0) {
                    $diff[$property] = $subDiff;
                }
            } else {
                $singleDiff = $this->compareProperty($oldNode, $newNode, $property);
                if ($singleDiff !== null) {
                    $diff[$property] = $singleDiff;
                }
            }
        }
        return $diff;
    }

    protected function node(stdClass $node, array $properties): array
    {
        $result = [];
        foreach ($properties as $property => $subProperties) {
            if (is_array($subProperties)) {
                $closure = $node->$property;
                $subDiff = [];
                foreach ($closure([])['edges'] ?? [] as $edge) {
                    $subDiff[] = $this->edge($edge, array_keys($subProperties));
                }
                if (count($subDiff) > 0) {
                    $result[$property] = $subDiff;
                }
            } elseif ($subProperties === Field::FILE) {
                $result[$property] = [
                    'filename' => ($node->$property->filename)(),
                    'filesize' => ($node->$property->filesize)(),
                    'mime_type' => ($node->$property->mime_type)(),
                    'url' => ($node->$property->url)(),
                ];
            } else {
                $result[$property] = $node->$property ?? null;
            }
        }
        return $result;
    }

    protected function edge(stdClass $edge, array $properties): array
    {
        $result = [];
        foreach ($properties as $property) {
            $result[$property] = $edge->$property ?? null;
        }
        return $result;
    }

    protected function compareEdge(array $oldEdges, array $newEdges, array $properties): array
    {
        $diff = [];
        $old = $this->getPropertiesFromEdges($oldEdges, $properties);
        $new = $this->getPropertiesFromEdges($newEdges, $properties);

        foreach (array_merge(array_keys($old), array_keys($new)) as $edgeId) {
            $oldEdge = $old[$edgeId] ?? [];
            $newEdge = $new[$edgeId] ?? [];
            $edgeDiff = [];
            foreach ($properties as $property) {
                if (($oldEdge[$property] ?? null) !== ($newEdge[$property] ?? null)) {
                    $edgeDiff[$property] = [
                        'oldValue' => $oldEdge[$property] ?? null,
                        'newValue' => $newEdge[$property] ?? null
                    ];
                }
            }
            if (count($edgeDiff) > 0) {
                $diff[$edgeId] = $edgeDiff;
            }
        }
        return $diff;
    }

    protected function getPropertiesFromEdges(array $edges, array $properties): array
    {
        $result = [];
        foreach ($edges as $edge) {
            $row = [];
            foreach ($properties as $property) {
                $row[$property] = $edge->$property ?? null;
            }
            if (count($row) > 0) {
                $result[$edge->parent_id] = $row;
            }
        }
        return $result;
    }

    protected function compareProperty(?stdClass $oldNode, stdClass $newNode, string $property): ?stdClass
    {
        if (!property_exists($newNode, $property)) {
            return null;
        }
        $oldValue = $oldNode->$property ?? null;
        $value = $newNode->$property;
        if ($value === $oldValue) {
            return null;
        }
        return (object)[
            'oldValue' => $oldValue,
            'newValue' => $value
        ];
    }

    protected function hash(array $entry): string
    {
        $data = json_encode($entry, JSON_THROW_ON_ERROR);
        $strong = false;
        $bytes = openssl_random_pseudo_bytes(17, $strong);
        if ((is_bool($bytes) && $bytes === false) || $strong === false) {
            throw new RuntimeException('Secure hash could not be created!');
        }
        $salt = '$6$rounds=5000$' . substr(base64_encode($bytes),0,22) . '$';
        return crypt($data, $salt);
    }
}