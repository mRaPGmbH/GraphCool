<?php
declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Providers;

use Carbon\Carbon;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DataProvider;
use Mrap\GraphCool\DataSource\QueryBuilders\MysqlQueryBuilder;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\Enums\ResultType;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\StopWatch;
use Mrap\GraphCool\Utils\TimeZone;
use PDO;
use PDOException;
use PDOStatement;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use stdClass;

class MysqlDataProvider extends DataProvider
{

    protected PDO $pdo;
    protected array $statements = [];
    protected int $search = 0;

    public function migrate(): void
    {
        $sql = 'SET sql_notes = 0';
        $this->pdo()->exec($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `node` (
              `id` char(36) NOT NULL COMMENT \'uuid\',
              `tenant_id` int(11) NOT NULL,
              `model` varchar(255) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $this->pdo()->exec($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `node_property` (
              `node_id` char(36) NOT NULL,
              `model` varchar(255) NOT NULL,
              `property` varchar(255) NOT NULL,
              `value_int` bigint(20) DEFAULT NULL,
              `value_string` longtext,
              `value_float` double DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`node_id`,`property`),
              CONSTRAINT `node_property_ibfk_2` FOREIGN KEY (`node_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $this->pdo()->exec($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `edge` (
              `parent_id` char(36) NOT NULL COMMENT \'node.id\',
              `child_id` char(36) NOT NULL COMMENT \'node.id\',
              `tenant_id` int(11) NOT NULL,
              `parent` varchar(255) NOT NULL,
              `child` varchar(255) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`parent_id`,`child_id`),
              KEY `child_id` (`child_id`),
              CONSTRAINT `edge_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `node` (`id`) ON DELETE CASCADE,
              CONSTRAINT `edge_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $this->pdo()->exec($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `edge_property` (
              `parent_id` char(36) NOT NULL COMMENT \'node.id\',
              `child_id` char(36) NOT NULL COMMENT \'node.id\',
              `parent` varchar(255) NOT NULL COMMENT \'node.name\',
              `child` varchar(255) NOT NULL COMMENT \'node.name\',
              `property` varchar(255) NOT NULL,
              `value_int` bigint(20) DEFAULT NULL,
              `value_string` longtext,
              `value_float` double DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`parent_id`,`child_id`,`child`),
              KEY `child_id` (`child_id`),
              KEY `key` (`child`),
              CONSTRAINT `edge_property_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `node` (`id`) ON DELETE CASCADE,
              CONSTRAINT `edge_property_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        $this->pdo()->exec($sql);

        $sql = 'SET sql_notes = 1';
        $this->pdo()->exec($sql);
    }

    public function findAll(string $name, array $args): stdClass
    {
        StopWatch::start(__METHOD__);
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $resultType = $args['result'] ?? ResultType::DEFAULT;

        $model = $this->getModel($name);
        $query = new MysqlQueryBuilder($model, $name);

        $query->select(['id'])
            ->limit($limit, $offset)
            ->where($args['where'] ?? null)
            ->orderBy($args['orderBy'] ?? [])
            ->search($args['search'] ?? null);

        match($resultType) {
            'ONLY_SOFT_DELETED' => $query->onlySoftDeleted(),
            'WITH_TRASHED' => $query->withTrashed(),
            default => null
        };

        $statement = $this->statement($query->toSql());
        $statement->execute($query->getParameters());
        $ids = [];
        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $row) {
            $ids[] = $row->id;
        }

        $statement = $this->statement($query->toCountSql());
        $statement->execute($query->getParameters());
        $total = (int)$statement->fetchColumn();

        $result = new stdClass();
        $result->paginatorInfo = $this->getPaginatorInfo(count($ids), $page, $limit, $total);
        StopWatch::stop(__METHOD__);
        $result->data = $this->loadAll($name, $ids, $resultType);
        return $result;
    }

    public function getMax(string $name, string $key): float|bool|int|string
    {
        $model = $this->getModel($name);
        $query = new MysqlQueryBuilder($model, $name);

        $valueType = match ($model->$key->type) {
            Type::BOOLEAN, Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET => 'value_int',
            Type::FLOAT, Field::DECIMAL => 'value_float',
            default => 'value_string'
        };

        $query->selectMax($key, 'max', $valueType)
            ->withTrashed();
        $statement = $this->statement($query->toSql());
        $statement->execute($query->getParameters());
        $result = $statement->fetch(PDO::FETCH_OBJ);

        $property = new stdClass();
        $property->$valueType = $result->max;

        return $this->convertDatabaseTypeToOutput($model->$key, $property);
    }

    public function loadAll(string $name, array $ids, ?string $resultType = ResultType::DEFAULT): array
    {
        $result = [];
        foreach ($ids as $id) {
            $result[] = $this->load($name, $id, $resultType);
        }
        return $result;
    }

    public function load(string $name, string $id, ?string $resultType = ResultType::DEFAULT): ?stdClass
    {
        StopWatch::start(__METHOD__);
        $node = $this->fetchNode($id, $resultType);
        if ($node === null) {
            return null;
        }
        if ($node->model !== $name) {
            throw new RuntimeException(
                'Class mismatch: expected "' . $name . '" but found "'
                . $node->model . '" instead (id:"' . $id . '")'
            );
        }
        $model = $this->getModel($name);
        foreach ($this->fetchNodeProperties($id) as $property) {
            $key = $property->property;
            if (!property_exists($model, $key)) {
                continue;
            }
            /** @var Field $field */
            $field = $model->$key;
            if ($field instanceof Relation) {
                continue;
            }
            $node->$key = $this->convertDatabaseTypeToOutput($field, $property);
        }

        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $node->$key = function (array $args) use ($id, $relation) {
                $result = $this->findRelatedNodes($id, $relation, $args);
                if (
                    $result !== null
                    && ($relation->type === Relation::HAS_ONE || $relation->type === Relation::BELONGS_TO)
                ) {
                    return $result[0] ?? null;
                }
                return $result;
            };
        }
        StopWatch::stop(__METHOD__);
        return $node;
    }

    public function insert(string $name, array $data): stdClass
    {
        $id = Uuid::uuid4()->toString();
        $this->insertNode($id, $name);

        $updates = $data['data'] ?? [];
        $model = $this->getModel($name);
        $updates = $model->beforeInsert($updates);
        foreach ($model as $key => $item) {
            if (!array_key_exists($key, $updates) && (
                    $item instanceof Relation ||
                    $item->null || in_array(
                            $item->type,
                            [
                                Type::ID,
                                Field::UPDATED_AT,
                                Field::DELETED_AT,
                                Field::CREATED_AT
                            ],
                            true
                        ))) {
                continue;
            }
            if ($item instanceof Relation && ($item->type === Relation::BELONGS_TO || $item->type === Relation::BELONGS_TO_MANY)) {
                $inputs = $updates[$key];
                if ($item->type === Relation::BELONGS_TO) {
                    $inputs = array($inputs);
                }
                $this->insertOrUpdateBelongsRelation($item, $inputs, $id, $name);
            } elseif ($item instanceof Field) {
                $this->insertOrUpdateModelField($item, $updates[$key] ?? null, $id, $name, $key);
            }
        }
        return $this->load($name, $id);
    }

    public function update(string $name, array $data): stdClass
    {
        $this->updateNode($data['id']);
        $model = $this->getModel($name);

        $updates = $data['data'] ?? [];
        foreach ($model as $key => $item) {
            if (!array_key_exists($key, $updates)) {
                continue;
            }
            if ($item instanceof Relation && ($item->type === Relation::BELONGS_TO || $item->type === Relation::BELONGS_TO_MANY)) {
                $inputs = $updates[$key];
                if ($item->type === Relation::BELONGS_TO) {
                    $inputs = array($inputs);
                }
                $this->insertOrUpdateBelongsRelation($item, $inputs, $data['id'], $name);
            } elseif ($item instanceof Field) {
                $this->insertOrUpdateModelField($item, $updates[$key], $data['id'], $name, $key);
            }
        }
        return $this->load($name, $data['id']);
    }

    public function updateMany(string $name, array $data): stdClass
    {
        $model = $this->getModel($name);
        $query = new MysqlQueryBuilder($model, $name);

        $query->update($data['data'] ?? [])
            ->where($data['where'] ?? null);

        match($args['result'] ?? ResultType::DEFAULT) {
            ResultType::ONLY_SOFT_DELETED => $query->onlySoftDeleted(),
            ResultType::WITH_TRASHED => $query->withTrashed(),
            default => null
        };

        $statement = $this->statement($query->toCountSql());
        $statement->execute($query->getParameters());
        $result = new stdClass();
        $result->updated_rows = (int)$statement->fetchColumn();

        $statement = $this->statement($query->toSql());
        $statement->execute($query->getUpdateParameters());

        return $result;
    }

    protected function insertOrUpdateModelField(Field $field, $value, $id, $name, $key): void
    {
        if ($value === null && $field->null === true) {
            $this->deleteNodeProperty($id, $key);
            return;
        }
        $dbValue = $this->convertInputTypeToDatabase($field, $value);
        if (is_int($dbValue)) {
            $this->insertOrUpdateNodeProperty($id, $name, $key, $dbValue, null, null);
        } elseif (is_float($dbValue)) {
            $this->insertOrUpdateNodeProperty($id, $name, $key, null, null, $dbValue);
        } else {
            $this->insertOrUpdateNodeProperty($id, $name, $key, null, $dbValue, null);
        }
    }

    protected function insertOrUpdateBelongsRelation(Relation $relation, array $data, string $childId, string $childName): void
    {
        $keep = [];
        foreach ($data as $row) {
            $parentId = $row['id'];
            $keep[] = '\'' . $parentId . '\'';
            unset($row['id']);
            $this->insertOrUpdateEdge($parentId, $childId, $relation->name, $childName);
            foreach ($relation as $key => $field)
            {
                if (!isset($row[$key])) {
                    continue;
                }
                $value = $this->convertInputTypeToDatabase($field, $row[$key]);
                if (is_int($value)) {
                    $this->insertOrUpdateEdgeProperty($parentId, $childId, $relation->name, $childName, $key, $value, null, null);
                } elseif (is_float($value)) {
                    $this->insertOrUpdateEdgeProperty($parentId, $childId, $relation->name, $childName, $key, null, null, $value);
                } else {
                    $this->insertOrUpdateEdgeProperty($parentId, $childId, $relation->name, $childName, $key, null, $value, null);
                }
            }
        }
        $sql = 'UPDATE `edge` SET `deleted_at` = now() WHERE `child_id` = :child_id';
        if (count($keep) > 0) {
            $sql .= ' AND parent_id NOT IN (' . implode(',', $keep) . ')';
        }
        $statement = $this->statement($sql);
        $statement->execute([
            ':child_id' => $childId
        ]);
    }

    protected function getPaginatorInfo(int $count, int $page, int $limit, int $total): stdClass
    {
        $paginatorInfo = new stdClass();
        $paginatorInfo->count = $count;
        $paginatorInfo->currentPage = $page;
        if ($total === 0) {
            $paginatorInfo->firstItem = 0;
        } else {
            $paginatorInfo->firstItem = 1;
        }
        $paginatorInfo->hasMorePages = $total > $page * $limit;
        $paginatorInfo->lastItem = $total;
        $paginatorInfo->lastPage = ceil($total / $limit);
        if ($paginatorInfo->lastPage < 1) {
            $paginatorInfo->lastPage = 1;
        }
        $paginatorInfo->perPage = $limit;
        $paginatorInfo->total = $total;
        return $paginatorInfo;
    }

    protected function statement(string $sql): PDOStatement
    {
        if (!isset($this->statements[$sql])) {
            $this->statements[$sql] = $this->pdo()->prepare($sql);
        }
        return $this->statements[$sql];
    }

    protected function pdo(): PDO
    {
        if (!isset($this->pdo)) {
            $connection = Env::get('DB_CONNECTION') . ':host=' . Env::get('DB_HOST') . ';port=' . Env::get('DB_PORT')
                . ';dbname=' . Env::get('DB_DATABASE');
            try {
                $this->pdo = new PDO(
                    $connection,
                    Env::get('DB_USERNAME'),
                    Env::get('DB_PASSWORD'),
                    [PDO::ATTR_PERSISTENT => true]
                );
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                throw new RuntimeException('Could not connect to database: ' . $connection . ' - user: ' . Env::get('DB_USERNAME') . ' ' . getenv('DB_USERNAME') . ' ' . print_r($_ENV, true));
            }
        }
        return $this->pdo;
    }


    protected function fetchNode(string $id, ?string $resultType = ResultType::DEFAULT): ?stdClass
    {
        $sql = 'SELECT * FROM `node` WHERE `node`.`tenant_id` = :tenant_id AND `id` = :id ';
        $sql .= match($resultType) {
            'ONLY_SOFT_DELETED' => 'AND `deleted_at` IS NOT NULL ',
            'WITH_TRASHED' => '',
            default => 'AND `deleted_at` IS NULL ',
        };

        $statement = $this->statement($sql);
        $statement->execute([
            ':tenant_id' => JwtAuthentication::tenantId(),
            ':id' => $id
        ]);
        $node = $statement->fetch(PDO::FETCH_OBJ);
        if ($node === false) {
            return null;
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

    protected function getModel(string $name): Model
    {
        $classname = 'App\\Models\\' . $name;
        return new $classname();
    }

    protected function fetchNodeProperties(string $id): array
    {
        $sql = 'SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL';
        $statement = $this->statement($sql);
        $statement->execute([':node_id' => $id]);
        return $statement->fetchAll(PDO::FETCH_OBJ);
    }

    protected function fetchEdgeProperties(string $parentId, string $childId): array
    {
        $sql = 'SELECT * FROM `edge_property` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `deleted_at` IS NULL';
        $statement = $this->statement($sql);
        $statement->execute([
            'parent_id' => $parentId,
            'child_id' => $childId
        ]);
        return $statement->fetchAll(PDO::FETCH_OBJ);
    }


    protected function convertDatabaseTypeToOutput(Field $field, stdClass $property): float|bool|int|string
    {
        return match ($field->type) {
            Type::BOOLEAN => (bool)$property->value_int,
            Type::FLOAT => (double)$property->value_float,
            Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET => (int)$property->value_int,
            Field::DECIMAL => (float)($property->value_int/(10 ** $field->decimalPlaces)),
            default => (string)$property->value_string,
        };
    }

    protected function convertInputTypeToDatabase(Field $field, $value): float|int|string|null
    {
        if ($field->null === false && $value === null) {
            $value = $field->default ?? null;
            if (is_null($value)) {
                return null;
            }
        }
        return match ($field->type) {
            default => (string)$value,
            Field::DATE, Field::DATE_TIME, Field::TIME, Field::TIMEZONE_OFFSET, Type::BOOLEAN, Type::INT => (int)$value,
            Type::FLOAT => (float)$value,
            Field::DECIMAL => (int)(round($value * (10 ** $field->decimalPlaces))),
        };
    }

    protected function findRelatedNodes(string $id, Relation $relation, array $args): array|stdClass
    {
        StopWatch::start(__METHOD__);
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $whereNode = $args['where'] ?? null;
        $whereEdge = $args['whereEdge'] ?? null;
        $search = $args['search'] ?? null;
        $orderBy = $args['orderBy'] ?? [];
        $resultType = $args['result'] ?? ResultType::DEFAULT;

        $edges = [];


        $query = new MysqlQueryBuilder($relation, $id);
        $query->select(['_child_id', '_parent_id'])->limit($limit, $offset)->where($whereEdge)->whereRelated($whereNode)->orderBy($orderBy)->search($search);
        match($resultType) {
            'ONLY_SOFT_DELETED' => $query->onlySoftDeleted(),
            'WITH_TRASHED' => $query->withTrashed(),
            default => null
        };

        $statement = $this->statement($query->toSql());
        $statement->execute($query->getParameters());

        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $edgeIds) {
            $edge = $this->fetchEdge($edgeIds->parent_id, $edgeIds->child_id);
            if ($edge === null) {
                continue;
            }
            $properties = $this->convertProperties($this->fetchEdgeProperties($edge->parent_id, $edge->child_id), $relation);
            foreach ($properties as $key => $value) {
                $edge->$key = $value;
            }
            if ($relation->type === Relation::HAS_ONE || $relation->type === Relation::HAS_MANY) {
                $edge->_node = $this->load($relation->name, $edge->child_id);
            } elseif ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::BELONGS_TO_MANY) {
                $edge->_node = $this->load($relation->name, $edge->parent_id);
            }
            $edges[] = $edge;
        }

        $statement = $this->statement($query->toCountSql());
        $statement->execute($query->getParameters());
        $total = (int)$statement->fetchColumn();

        StopWatch::stop(__METHOD__);
        if ($relation->type === Relation::HAS_MANY || $relation->type === Relation::BELONGS_TO_MANY) {
            $count = count($edges);
            return [
                'paginatorInfo' => $this->getPaginatorInfo($count, $page, $limit, $total),
                'edges' => $edges,
            ];
        }
        return $edges;
    }

    protected function fetchEdge(string $parentId, string $childId): ?stdClass
    {
        $sql = 'SELECT * FROM `edge` WHERE `tenant_id` = :tenant_id AND `parent_id` = :parent_id AND `child_id` = :child_id AND `deleted_at` IS NULL';
        $statement = $this->statement($sql);
        $statement->execute([
            'tenant_id' => JwtAuthentication::tenantId(),
            'parent_id' => $parentId,
            'child_id' => $childId
        ]);
        $edge = $statement->fetch(PDO::FETCH_OBJ);
        if ($edge === false) {
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

    protected function convertProperties(array $properties, Model|Relation $fieldSource): array
    {
        $result = [];
        foreach ($properties as $property) {
            $key = $property->property;
            if (!property_exists($fieldSource, $key)) {
                continue;
            }
            /** @var Field $field */
            $field = $fieldSource->$key;
            $result[$key] = $this->convertDatabaseTypeToOutput($field, $property);
        }
        return $result;
    }


    protected function insertNode(string $id, string $name): bool
    {
        $sql = 'INSERT INTO `node` (`id`, `tenant_id`, `model`) VALUES (:id, :tenant_id, :model)';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':id'    => $id,
                ':tenant_id' => JwtAuthentication::tenantId(),
                ':model' => $name
            ]
        );
    }

    protected function insertOrUpdateEdge(string $parentId, string $childId, string $parent, string $child): bool
    {
        $sql = 'INSERT INTO `edge` (`parent_id`, `child_id`, `tenant_id`, `parent`, `child`) VALUES 
            (:parent_id, :child_id, :tenant_id, :parent, :child)
            ON DUPLICATE KEY UPDATE `deleted_at` = null';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':parent_id' => $parentId,
                ':child_id'  => $childId,
                ':tenant_id' => JwtAuthentication::tenantId(),
                ':parent'    => $parent,
                ':child'     => $child,
            ]
        );
    }

    protected function insertOrUpdateNodeProperty(
        string $nodeId,
        string $name,
        string $propertyName,
        ?int $valueInt,
        ?string $valueString,
        ?float $valueFloat
    ): bool {
        $sql = 'INSERT INTO `node_property` (`node_id`, `model`, `property`, `value_int`, `value_string`, `value_float`) '
            . 'VALUES (:node_id, :model, :property, :value_int, :value_string, :value_float) '
            . 'ON DUPLICATE KEY UPDATE `value_int` = :value_int2, `value_string` = :value_string2, `value_float` = :value_float2, `deleted_at` = NULL';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':node_id'       => $nodeId,
                ':model'         => $name,
                ':property'      => $propertyName,
                ':value_int'     => $valueInt,
                ':value_string'  => $valueString,
                ':value_float'   => $valueFloat,
                ':value_int2'    => $valueInt,
                ':value_string2' => $valueString,
                ':value_float2'   => $valueFloat
            ]
        );
    }

    protected function insertOrUpdateEdgeProperty(
        string $parentId,
        string $childId,
        string $parent,
        string $child,
        string $propertyName,
        ?int $valueInt,
        ?string $valueString,
        ?float $valueFloat
    ): bool {
        $sql = 'INSERT INTO `edge_property` (`parent_id`, `child_id`, `parent`, `child`, `property`, `value_int`, `value_string`, `value_float`) '
            . 'VALUES (:parent_id, :child_id, :parent, :child, :property, :value_int, :value_string, :value_float) '
            . 'ON DUPLICATE KEY UPDATE `value_int` = :value_int2, `value_string` = :value_string2, `value_float` = :value_float2, `deleted_at` = NULL';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':parent_id'       => $parentId,
                ':child_id'       => $childId,
                ':parent'         => $parent,
                ':child'         => $child,
                ':property'      => $propertyName,
                ':value_int'     => $valueInt,
                ':value_string'  => $valueString,
                ':value_float'   => $valueFloat,
                ':value_int2'    => $valueInt,
                ':value_string2' => $valueString,
                ':value_float2'   => $valueFloat
            ]
        );
    }


    protected function updateNode(string $id): bool
    {
        $sql = 'UPDATE `node` SET `updated_at` = now() WHERE `tenant_id` = :tenant_id AND id = :id';
        $statement = $this->statement($sql);
        return $statement->execute([
            ':id' => $id,
            ':tenant_id' => JwtAuthentication::tenantId()
        ]);
    }

    protected function deleteNodeProperty(string $nodeId, string $propertyName): bool
    {
        $sql = 'UPDATE `node_property` SET `deleted_at` = now() '
            . 'WHERE `deleted_at` IS NULL AND `node_id` = :node_id AND `property` = :property';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':node_id'  => $nodeId,
                ':property' => $propertyName,
            ]
        );
    }

    public function delete(string $name, string $id): ?stdClass
    {
        $model = $this->load($name, $id);
        $this->deleteNode($id);
        $this->deleteEdgesForNodeId($id);
        return $model;
    }

    public function restore(string $name, string $id): stdClass
    {
        $this->restoreNode($id);
       return $this->load($name, $id);
    }

    protected function deleteNode(string $id): bool
    {
        $sql = 'UPDATE `node` SET `deleted_at` = now() WHERE `tenant_id` = :tenant_id AND `id` = :id';
        $statement = $this->statement($sql);
        return $statement->execute([
            ':id' => $id,
            ':tenant_id' => JwtAuthentication::tenantId()
        ]);
    }

    protected function deleteEdgesForNodeId(string $id): bool
    {
        $sql = 'UPDATE `edge` SET `deleted_at` = now() WHERE `tenant_id` = :tenant_id AND `parent_id` = :id1 OR `child_id` = :id2';
        $statement = $this->statement($sql);
        return $statement->execute([
            ':tenant_id' => JwtAuthentication::tenantId(),
            ':id1' => $id,
            ':id2' => $id
        ]);
    }

    protected function restoreNode(string $id): bool
    {
        $sql = 'UPDATE `node` SET `deleted_at` = NULL WHERE `tenant_id` = :tenant_id AND `id` = :id';
        $statement = $this->statement($sql);
        return $statement->execute([
            ':id' => $id,
            ':tenant_id' => JwtAuthentication::tenantId()
        ]);
    }

}