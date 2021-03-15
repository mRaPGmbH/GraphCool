<?php
declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Providers;

use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DataProvider;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\Enums\ResultType;
use Mrap\GraphCool\Utils\Env;
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
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $where = $args['where'] ?? null;
        $orderBy = [ // TODO: multi order
            'field' => $args['orderBy'][0]['field'] ?? 'created_at',
            'order' => $args['orderBy'][0]['order'] ?? 'ASC',
        ];
        $search = $args['search'] ?? null;
        $resultType = $args['result'] ?? ResultType::DEFAULT;

        [$ids, $total] = $this->findNodes($name, $where, $limit, $offset, $orderBy, $search, $resultType);

        $result = new stdClass();
        $result->paginatorInfo = $this->getPaginatorInfo(count($ids), $page, $limit, $total);
        $result->data = $this->loadAll($name, $ids, $resultType);
        return $result;
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

        return $node;
    }

    public function insert(string $name, array $data): stdClass
    {
        $id = Uuid::uuid4()->toString();
        $this->insertNode($id, $name);

        $model = $this->getModel($name);
        foreach ($model as $key => $item) {
            if (!array_key_exists($key, $data) && (
                    $item instanceof Relation || (
                        $item->null && !in_array(
                            $item->type,
                            [
                                Type::ID,
                                Field::UPDATED_AT,
                                Field::DELETED_AT,
                                Field::CREATED_AT
                            ],
                            true
                        )))) {
                continue;
            }
            if ($item instanceof Relation && ($item->type === Relation::BELONGS_TO || $item->type === Relation::BELONGS_TO_MANY)) {
                $inputs = $data[$key];
                if ($item->type === Relation::BELONGS_TO) {
                    $inputs = array($inputs);
                }
                $this->insertOrUpdateBelongsRelation($item, $inputs, $id, $name);
            } elseif ($item instanceof Field) {
                $this->insertOrUpdateModelField($item, $data[$key] ?? null, $id, $name, $key);
            }
        }
        return $this->load($name, $id);
    }

    public function update(string $name, array $data): stdClass
    {
        $this->updateNode($data['id']);
        $model = $this->getModel($name);
        foreach ($model as $key => $item) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            if ($item instanceof Relation && ($item->type === Relation::BELONGS_TO || $item->type === Relation::BELONGS_TO_MANY)) {
                $inputs = $data[$key];
                if ($item->type === Relation::BELONGS_TO) {
                    $inputs = array($inputs);
                }
                $this->insertOrUpdateBelongsRelation($item, $inputs, $data['id'], $name);
            } elseif ($item instanceof Field) {
                $this->insertOrUpdateModelField($item, $data[$key], $data['id'], $name, $key);
            }
        }
        return $this->load($name, $data['id']);
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
        $paginatorInfo->firstItem = 1;
        $paginatorInfo->hasMorePages = $total > $page * $limit;
        $paginatorInfo->lastItem = $total;
        $paginatorInfo->lastPage = ceil($total / $limit);
        $paginatorInfo->perPage = $limit;
        $paginatorInfo->total = $total;
        return $paginatorInfo;
    }

    protected function findNodes(
        string $name,
        ?array $where,
        int $limit = 10,
        int $offset = 0,
        array $orderBy,
        string $search = null,
        string $resultType
    ): array {
        [$sql, $parameters, $order] = $this->buildFindSql($where, $name, $orderBy, $search, $resultType);
        $order .= ' LIMIT ' . $offset . ', ' . $limit;

        StopWatch::start('SELECT `n`.`id` ' . $sql . ' GROUP BY `n`.`id` ' . $order);
        $statement = $this->statement('SELECT `n`.`id` ' . $sql . ' GROUP BY `n`.`id` ' . $order);

        $statement->execute($parameters);
        $result = [];
        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $row) {
            $result[] = $row->id;
        }

        $statement = $this->statement('SELECT count(DISTINCT `n`.`id`) ' . $sql);
        $statement->execute($parameters);
        $total = (int)$statement->fetchColumn();
        StopWatch::stop('SELECT `n`.`id` ' . $sql . ' GROUP BY `n`.`id` ' . $order);
        return [$result, $total];
    }


    protected function buildFindSql(?array $where, string $model, array $orderBy, ?string $search = null, string $resultType)
    {
        $joins = [];
        $whereSql = ' WHERE `n`.`model` = :model ';
        $whereSql .= match($resultType) {
            'ONLY_SOFT_DELETED' => 'AND `n`.`deleted_at` IS NOT NULL ',
            'WITH_TRASHED' => '',
            default => 'AND `n`.`deleted_at` IS NULL ',
        };

        $parameters = [
            ':model' => $model
        ];

        if (!empty($where)) {
            $whereSql .= ' AND ' . $this->buildWhereSql($where, $joins, $parameters) . ' ';
        }
        if (in_array($orderBy['field'], ['created_at', 'updated_at', 'deleted_at', 'id'])) {
            $orderSql = ' ORDER BY `n`.`' . $orderBy['field'] . '` ' . $orderBy['order'];
        } else {
            $joins[] = 'LEFT JOIN `node_property` AS `order` ON 
                (`order`.`node_id` = `n`.`id` AND `order`.`property` = \'' . $orderBy['field'] . '\')';
            $orderSql = ' ORDER BY `order`.`value_string` ' . $orderBy['order']
             . ', `order`.`value_int` ' . $orderBy['order']
             . ', `order`.`value_float` ' . $orderBy['order'];
        }

        if ($search !== null) {
            $joins[] = 'LEFT JOIN `node_property` AS `search` ON 
                (`search`.`node_id` = `n`.`id` AND `search`.`value_string` IS NOT NULL)';
            $whereSql .= ' AND `search`.`value_string` LIKE :searchString ';
            $parameters[':searchString'] = '%' . $search . '%';
        }

        $sql = 'FROM `node` AS `n` ' . implode(' ', $joins) . $whereSql;

        return [$sql, $parameters, $orderSql];
    }

    protected function buildWhereSql(?array $wheres, array &$joins, array &$parameters, string $mode = 'AND', string $base = 'node'): string
    {
        $s = $base[0];
        if (
            isset($wheres['operator'])
            || isset($wheres['value'])
            || isset($wheres['column'])
            || isset($wheres['AND'])
            || isset($wheres['OR'])
        ) {
            $wheres = [$wheres];
        }
        $sqls = [];
        foreach ($wheres as $where) {
            if (isset($where['operator']) || isset($where['value']) || isset($where['column'])) {
                $i = count($parameters);
                if (in_array($where['column'], ['created_at', 'updated_at', 'deleted_at', 'id'])) {
                    $sql = '`' . $s . '`.`' . $where['column'] . '` '. $where['operator'] . ' :' . $s . 'p' . $i;
                    $parameters[':' . $s . 'p' . $i] = $where['value'];
                } else {
                    $joins[] = 'LEFT JOIN `' . $base . '_property` AS `' . $s . 'p' . $i . '` ON `' . $s . 'p' . $i . '`.`' . $base . '_id` = `' . $s . '`.`id`';
                    $sql = '`' . $s . 'p' . $i . '`.`property` = :' . $s . 'p' . $i . 'p AND `' . $s . 'p' . $i . '`.`deleted_at` IS NULL ';
                    $parameters[':' . $s . 'p' . $i . 'p'] = $where['column'];
                    $sql .= ' AND ';
                    if (is_bool($where['value']) || is_int($where['value'])) {
                        $sql .= '`' . $s . 'p' . $i . '`.`value_int` ' . $where['operator'] . ' :' . $s . 'p' . $i . 'i';
                        $parameters[':' . $s . 'p' . $i . 'i'] = (int) $where['value'];
                    } elseif (is_float($where['value'])) {
                        $sql .= '`' . $s . 'p' . $i . '`.`value_int` ' . $where['operator'] . ' :' . $s . 'p' . $i . 'f';
                        $parameters[':' . $s . 'p' . $i . 'f'] = $where['value'];
                    } else {
                        $sql .= '`' . $s . 'p' . $i . '`.`value_string` ' . $where['operator'] . ' :' . $s . 'p' . $i . 's';
                        $parameters[':' . $s . 'p' . $i . 's'] = (string) $where['value'];
                    }
                }
                $sqls[] = '(' . $sql . ')';
            }
            if (isset($where['OR'])) {
                $sqls[] = $this->buildWhereSql($where['OR'], $joins, $parameters, 'OR');
            }
            if (isset($where['AND'])) {
                $sqls[] = $this->buildWhereSql($where['AND'], $joins, $parameters, 'AND');
            }
        }
        return '(' . implode(' ' . $mode . ' ', $sqls) . ')';
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
        $sql = 'SELECT * FROM `node` WHERE `id` = :id ';
        $sql .= match($resultType) {
            'ONLY_SOFT_DELETED' => 'AND `deleted_at` IS NOT NULL ',
            'WITH_TRASHED' => '',
            default => 'AND `deleted_at` IS NULL ',
        };

        $statement = $this->statement($sql);
        $statement->execute([':id' => $id]);
        $node = $statement->fetch(PDO::FETCH_OBJ);

        $dates = ['updated_at', 'created_at', 'deleted_at'];
        foreach ($dates as $date) {
            if ($node->$date !== null) {
                $dateTime = new \DateTime();
                $dateTime->setTimestamp(strtotime($node->$date));
                $dateTime->setTimezone(TimeZone::get());
                $node->$date = $dateTime->format(\DateTime::ATOM);
            }
        }
        if ($node === false) {
            return null;
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
            return $field->default ?? null;
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
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $whereNode = $args['where'] ?? null;
        $whereEdge = $args['whereEdge'] ?? null;
        $search = $args['search'] ?? null;
        $orderBy = [ // TODO: multi order
            'field' => $args['orderBy'][0]['field'] ?? 'created_at',
            'order' => $args['orderBy'][0]['order'] ?? 'ASC',
        ];
        $resultType = $args['result'] ?? ResultType::DEFAULT;

        $edges = [];
        $total = 0;
        if ($relation->type === Relation::HAS_ONE || $relation->type === Relation::HAS_MANY) {
            [$edges, $total] = $this->findChildren($id, $relation, $whereNode, $whereEdge, $limit, $offset, $orderBy, $search, $resultType);
        } elseif ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::BELONGS_TO_MANY) {
            [$edges, $total] = $this->findParents($id, $relation, $whereNode, $whereEdge, $limit, $offset, $orderBy, $search, $resultType);
        }

        if ($relation->type === Relation::HAS_MANY || $relation->type === Relation::BELONGS_TO_MANY) {
            $count = count($edges);
            return [
                'paginatorInfo' => $this->getPaginatorInfo($count, $page, $limit, $total),
                'edges' => $edges,
            ];
        }
        return $edges;
    }

    protected function findChildren(
        string $parentId,
        Relation $relation,
        ?array $whereNode,
        ?array $whereEdge,
        int $limit = 10,
        int $offset = 0,
        array $orderBy,
        ?string $search,
        string $resultType = ResultType::DEFAULT
    ): array
    {
        [$sql, $parameters, $orderSql] = $this->buildChildFindSql($parentId, $relation->name, $whereNode, $whereEdge, $search, $orderBy, $resultType);
        $limitSql = ' LIMIT ' . $offset . ', ' . $limit;

        //print_r(['SELECT `e`.`child_id` ' . $sql . ' GROUP BY `e`.`child_id`',$parameters]);

        StopWatch::start('SELECT `e`.`child_id` ' . $sql . ' GROUP BY `e`.`child_id` ' . $orderSql . ' ' . $limitSql);
        $statement = $this->statement('SELECT `e`.`child_id` ' . $sql . ' GROUP BY `e`.`child_id` ' . $orderSql . ' ' . $limitSql);

        $statement->execute($parameters);
        $result = [];
        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $row) {
            $edge = $this->fetchEdge($parentId, $row->child_id);
            $properties = $this->convertProperties($this->fetchEdgeProperties($parentId, $row->child_id), $relation);
            $row = [];
            foreach ($properties as $key => $value) {
                $row[$key] = $value;
            }
            $row['_node'] = $this->load($edge->child, $edge->child_id);
            $result[] = $row;
        }

        $statement = $this->statement('SELECT count(DISTINCT `e`.`child_id`) ' . $sql);
        $statement->execute($parameters);
        $total = (int)$statement->fetchColumn();
        StopWatch::stop('SELECT `e`.`child_id` ' . $sql . ' GROUP BY `e`.`child_id` ' . $orderSql . ' ' . $limitSql );
        return [$result, $total];
    }

    protected function buildChildFindSql(string $parentId, string $childName, ?array $whereNode, ?array $whereEdge, ?string $search, array $orderBy, string $resultType)
    {
        $joins = [];
        $whereSql = ' WHERE `e`.`parent_id` = :parentId AND `e`.`child` = :child ';
        $whereSql .= match($resultType) {
            'ONLY_SOFT_DELETED' => 'AND `e`.`deleted_at` IS NOT NULL ',
            'WITH_TRASHED' => '',
            default => 'AND `e`.`deleted_at` IS NULL ',
        };

        $parameters = [
            ':parentId' => $parentId,
            ':child' => $childName
        ];

        if (!empty($whereNode)) {
            $whereSql .= ' AND ' . $this->buildWhereSql($whereNode, $joins, $parameters, 'node') . ' ';
        }
        if (!empty($whereEdge)) {
            $whereSql .= ' AND ' . $this->buildWhereSql($whereEdge, $joins, $parameters, 'edge') . ' ';
        }

        if ($search !== null) {
            $whereSql .= $this->buildRelatedSearchSql($joins, $parameters, $search);
        }
        $orderSql = $this->buildRelatedOrderBySql($joins, $orderBy);

        $sql = 'FROM `edge` AS `e` LEFT JOIN `node` AS `n`ON `n`.`id` = `e`.`child_id` ' . implode(' ', $joins) . $whereSql;

        //var_dump($sql);
        return [$sql, $parameters, $orderSql];
    }

    protected function buildRelatedSearchSql(array &$joins, array &$parameters, string $search): string
    {
        $joins[] = 'LEFT JOIN `edge_property` AS `search' . (++$this->search)  . '` ON 
                (`search' . $this->search  . '`.`parent_id` = `e`.`parent_id` 
                AND `search' . $this->search  . '`.`child_id` = `e`.`child_id`
                AND `search' . $this->search  . '`.`value_string` IS NOT NULL)';
        $sql = ' AND (`search' . $this->search  . '`.`value_string` LIKE :searchString' . $this->search;
        $parameters[':searchString' . $this->search] = '%' . $search . '%';

        $joins[] = 'LEFT JOIN `node_property` AS `search' . (++$this->search)  . '` ON 
                (`search' . $this->search  . '`.`node_id` = `n`.`id` AND `search' . $this->search  . '`.`value_string` IS NOT NULL)';
        $sql .= ' OR `search' . $this->search  . '`.`value_string` LIKE :searchString' . $this->search  . ') ';
        $parameters[':searchString' . $this->search] = '%' . $search . '%';

        return $sql;
    }

    protected function buildRelatedOrderBySql(array &$joins, array $orderBy): string
    {
        $orderField = $orderBy['field'];
        if (str_starts_with($orderField, '_')) { // sort by edge
            $orderField = substr($orderField, 1);
            if (in_array($orderField, ['created_at', 'updated_at', 'deleted_at', 'id'])) {
                $orderSql = ' ORDER BY `e`.`' . $orderField . '` ' . $orderBy['order'];
            } else {
                $joins[] = 'LEFT JOIN `edge_property` AS `order` ON
                    (`order`.`parent_id` = `e`.`parent_id` AND `order`.`child_id` = `e`.`child_id` AND `order`.`property` = \'' . $orderField . '\')';
                $orderSql = ' ORDER BY max(`order`.`value_string`) ' . $orderBy['order']
                    . ', max(`order`.`value_int`) ' . $orderBy['order']
                    . ', max(`order`.`value_float`) ' . $orderBy['order'];
            }
        } else { // sort by node
            if (in_array($orderField, ['created_at', 'updated_at', 'deleted_at', 'id'])) {
                $orderSql = ' ORDER BY `n`.`' . $orderField . '` ' . $orderBy['order'];
            } else {
                $joins[] = 'LEFT JOIN `node_property` AS `order` ON
                (`order`.`node_id` = `n`.`id` AND `order`.`property` = \'' . $orderField . '\')';
                $orderSql = ' ORDER BY max(`order`.`value_string`) ' . $orderBy['order']
                    . ', max(`order`.`value_int`) ' . $orderBy['order']
                    . ', max(`order`.`value_float`) ' . $orderBy['order'];
            }
        }
        return $orderSql;
    }

    protected function findParents(
        string $childId,
        Relation $relation,
        ?array $whereNode,
        ?array $whereEdge,
        int $limit = 10,
        int $offset = 0,
        array $orderBy,
        ?string $search = null,
        string $resultType = ResultType::DEFAULT
    ): array
    {
        [$sql, $parameters, $orderSql] = $this->buildParentFindSql($childId, $relation->name, $whereNode, $whereEdge, $search, $orderBy, $resultType);
        $limitSql = ' LIMIT ' . $offset . ', ' . $limit;

        $statement = $this->statement('SELECT `e`.`parent_id` ' . $sql . ' GROUP BY `e`.`parent_id` ' . $orderSql . ' ' . $limitSql );

        $statement->execute($parameters);
        $result = [];
        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $row) {
            $edge = $this->fetchEdge($row->parent_id, $childId);
            $properties = $this->convertProperties($this->fetchEdgeProperties($row->parent_id, $childId), $relation);
            $row = [];
            foreach ($properties as $key => $value) {
                $row[$key] = $value;
            }
            $row['_node'] = $this->load($edge->parent, $edge->parent_id);
            $result[] = $row;
        }

        $statement = $this->statement('SELECT count(DISTINCT `e`.`parent_id`) ' . $sql);
        $statement->execute($parameters);
        $total = (int)$statement->fetchColumn();
        return [$result, $total];
    }

    protected function buildParentFindSql(string $childId, string $parentName, ?array $whereNode, ?array $whereEdge, ?string $search, array $orderBy, string $resultType)
    {
        $joins = [];
        $whereSql = ' WHERE `e`.`child_id` = :childId AND `e`.`parent` = :parent ';
        $whereSql .= match($resultType) {
            'ONLY_SOFT_DELETED' => 'AND `e`.`deleted_at` IS NOT NULL ',
            'WITH_TRASHED' => '',
            default => 'AND `e`.`deleted_at` IS NULL ',
        };

        $parameters = [
            ':childId' => $childId,
            ':parent' => $parentName
        ];

        if (!empty($whereNode)) {
            $whereSql .= ' AND ' . $this->buildWhereSql($whereNode, $joins, $parameters, 'node') . ' ';
        }
        if (!empty($whereEdge)) {
            $whereSql .= ' AND ' . $this->buildWhereSql($whereEdge, $joins, $parameters, 'edge') . ' ';
        }

        if ($search !== null) {
            $whereSql .= $this->buildRelatedSearchSql($joins, $parameters, $search);
        }
        $orderSql = $this->buildRelatedOrderBySql($joins, $orderBy);

        $sql = 'FROM `edge` AS `e` LEFT JOIN `node` AS `n`ON `n`.`id` = `e`.`parent_id` ' . implode(' ', $joins) . $whereSql;

        return [$sql, $parameters, $orderSql];
    }

    protected function fetchEdge(string $parentId, string $childId): ?stdClass
    {
        $sql = 'SELECT * FROM `edge` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `deleted_at` IS NULL';
        $statement = $this->statement($sql);
        $statement->execute([
            'parent_id' => $parentId,
            'child_id' => $childId
        ]);
        $edge = $statement->fetch(PDO::FETCH_OBJ);
        if ($edge === false) {
            return null;
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
        $sql = 'INSERT INTO `node` (`id`, `model`) VALUES (:id, :model)';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':id'    => $id,
                ':model' => $name
            ]
        );
    }

    protected function insertOrUpdateEdge(string $parentId, string $childId, string $parent, string $child): bool
    {
        $sql = 'INSERT INTO `edge` (`parent_id`, `child_id`, `parent`, `child`) VALUES 
            (:parent_id, :child_id, :parent, :child)
            ON DUPLICATE KEY UPDATE `deleted_at` = null';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':parent_id' => $parentId,
                ':child_id'  => $childId,
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
        $sql = 'UPDATE node SET updated_at = now() WHERE id = :id';
        $statement = $this->statement($sql);
        return $statement->execute([':id' => $id]);
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

    public function delete(string $name, string $id): stdClass
    {
        $model = $this->load($name, $id);
        $this->deleteNode($id);
        return $model;
    }

    public function restore(string $name, string $id): stdClass
    {
        $this->restoreNode($id);
       return $this->load($name, $id);
    }

    protected function deleteNode(string $id): bool
    {
        $sql = 'UPDATE `node` SET `deleted_at` = now() WHERE `id` = :id';
        $statement = $this->statement($sql);
        return $statement->execute([':id' => $id]);
    }

    protected function restoreNode(string $id): bool
    {
        $sql = 'UPDATE `node` SET `deleted_at` = NULL WHERE `id` = :id';
        $statement = $this->statement($sql);
        return $statement->execute([':id' => $id]);
    }


}