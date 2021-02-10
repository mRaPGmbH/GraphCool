<?php


namespace Mrap\GraphCool\DataSource\Providers;

use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DataProvider;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Utils\Env;
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

    public function migrate(): void
    {
        // TODO
    }

    public function findAll(string $name, array $args): stdClass
    {
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $where = $args['where'] ?? null;

        [$ids, $total] = $this->findNodes($name, $where, $limit, $offset);

        $result = new stdClass();
        $result->paginatorInfo = $this->getPaginatorInfo(count($ids), $page, $limit, $total);
        $result->data = $this->loadAll($name, $ids);
        return $result;
    }

    public function loadAll(string $name, array $ids): ?array
    {
        $result = [];
        foreach ($ids as $id) {
            $result[] = $this->load($name, $id);
        }
        if (count($result) === 0) {
            return null;
        }
        return $result;
    }

    public function load(string $name, string $id): ?stdClass
    {
        // \App\Timer::start(__METHOD__);
        $node = $this->fetchNode($id);
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
                    return $result[0];
                }
                return $result;
            };
        }

        //\App\Timer::stop(__METHOD__);
        return $node;
    }

    public function insert(string $name, array $data): stdClass
    {
        //\App\Timer::start(__METHOD__);
        $id = Uuid::uuid4();
        $this->insertNode($id, $name);

        $model = $this->getModel($name);
        foreach ($model as $key => $item) {
            if ($item instanceof Relation && $item->type === Relation::BELONGS_TO) {
                $fullKey = $key . '_id';
                if (!isset($data[$fullKey])) {
                    continue;
                }
                $value = $data[$fullKey];
                $this->insertEdge($value, $id, $item->name, $name);
            } elseif ($item instanceof Field) {
                if (!isset($data[$key])) {
                    continue;
                }
                $value = $data[$key];
                if ($value === null) {
                    continue;
                }
                $dbValue = $this->convertInputTypeToDatabase($item, $value);
                if (is_int($dbValue)) {
                    $this->insertOrUpdateNodeProperty($id, $name, $key, $dbValue, null, null);
                } elseif (is_float($dbValue)) {
                    $this->insertOrUpdateNodeProperty($id, $name, $key, null, null, $dbValue);
                } else {
                    $this->insertOrUpdateNodeProperty($id, $name, $key, null, $dbValue, null);
                }
            }
        }
        //\App\Timer::stop(__METHOD__);
        return $this->load($name, $id);
    }

    public function update(string $name, array $data): stdClass
    {
        //\App\Timer::start(__METHOD__);
        $this->updateNode($data['id']);
        $model = $this->getModel($name);
        foreach ($model as $key => $item) {
            if (!$item instanceof Field || !isset($data[$key])) {
                continue;
            }
            $value = $data[$key];
            if ($value === null) {
                $this->deleteNodeProperty($data['id'], $key);
            }
            $dbValue = $this->convertInputTypeToDatabase($item, $value);
            if (is_int($dbValue)) {
                $this->insertOrUpdateNodeProperty($data['id'], $name, $key, $dbValue, null, null);
            } elseif (is_float($dbValue)) {
                $this->insertOrUpdateNodeProperty($data['id'], $name, $key, null, null, $dbValue);
            } else {
                $this->insertOrUpdateNodeProperty($data['id'], $name, $key, null, $dbValue, null);
            }
        }
        //\App\Timer::stop(__METHOD__);
        return $this->load($name, $data['id']);
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
        string $orderBy = '`n`.`created_at` ASC'
    ): array {
        [$sql, $parameters] = $this->buildFindSql($where, $name);
        $order = 'ORDER BY ' . $orderBy . ' LIMIT ' . $offset . ', ' . $limit;

        $statement = $this->statement('SELECT `n`.`id` ' . $sql . ' GROUP BY `n`.`id` ' . $order);

        $statement->execute($parameters);
        $result = [];
        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $row) {
            $result[] = $row->id;
        }
        $statement = $this->statement('SELECT count(DISTINCT `n`.`id`) ' . $sql);
        $statement->execute($parameters);
        $total = (int)$statement->fetchColumn();
        return [$result, $total];
    }


    protected function buildFindSql(?array $where, string $model)
    {
        $joins = [];
        $whereSql = ' WHERE `n`.`model` = :model AND `n`.`deleted_at` IS NULL ';
        $parameters = [
            ':model' => $model
        ];

        if (!empty($where)) {
            $whereSql .= ' AND ' . $this->buildWhereSql($where, $joins, $parameters) . ' ';
        }

        $sql = 'FROM `node` AS `n` ' . implode(' ', $joins) . $whereSql;

        return [$sql, $parameters];
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
                $joins[] = 'LEFT JOIN `' . $base . '_property` AS `' . $s . 'p' . $i . '` ON `' . $s . 'p' . $i . '`.`' . $base . '_id` = `' . $s . '`.`id`';
                $sql = '`' . $s . 'p' . $i . '`.`property` = :' . $s . 'p' . $i . 'p AND `' . $s . 'p' . $i . '`.`deleted_at` IS NULL ';
                $parameters[':' . $s . 'p' . $i . 'p'] = $where['column'];
                if (is_string($where['value'])) {
                    $sql .= ' AND ';
                    $sql .= '`' . $s . 'p' . $i . '`.`value_string` ' . $where['operator'] . ' :' . $s . 'p' . $i . 's'; // TODO: non-strings!
                    $parameters[':' . $s . 'p' . $i . 's'] = $where['value'];
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
            //\App\Timer::start(__METHOD__);
            $this->statements[$sql] = $this->pdo()->prepare($sql);
            //\App\Timer::stop(__METHOD__);
        }
        return $this->statements[$sql];
    }

    protected function pdo(): PDO
    {
        if (!isset($this->pdo)) {
            //\App\Timer::start(__METHOD__);
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
                throw new RuntimeException('Could not connect to database: ' . $connection);
            }
            //\App\Timer::stop(__METHOD__);
        }
        return $this->pdo;
    }


    protected function fetchNode(string $id): ?stdClass
    {
        $sql = 'SELECT * FROM `node` WHERE `id` = :id AND deleted_at IS NULL';
        $statement = $this->statement($sql);
        $statement->execute([':id' => $id]);
        $node = $statement->fetch(PDO::FETCH_OBJ);
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
            Type::INT => (int)$property->value_int,
            Field::DECIMAL => (float)($property->value_int/(10 ** $field->decimalPlaces)),
            Field::DATE => (string)date('Y-m-d', $property->value_int),
            Field::DATE_TIME => (string)date('Y-m-d H:i:s', $property->value_int),
            Field::TIME => (string)date('H:i:s', $property->value_int),
            default => (string)$property->value_string,
        };
    }

    protected function convertInputTypeToDatabase(Field $field, $value): float|int|string
    {
        return match ($field->type) {
            default => (string)$value,
            Type::BOOLEAN, Type::INT => (int)$value,
            Type::FLOAT => (float)$value,
            Field::DECIMAL => (int)(round($value * (10 ** $field->decimalPlaces))),
            Field::DATE, Field::DATE_TIME, Field::TIME => (int)strtotime($value),
        };
    }

    protected function findRelatedNodes(string $id, Relation $relation, array $args): array|stdClass
    {
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $whereNode = $args['where'] ?? null;
        $whereEdge = $args['whereEdge'] ?? null;

        $edges = [];
        $total = 0;
        if ($relation->type === Relation::HAS_ONE || $relation->type === Relation::HAS_MANY) {
            [$edges, $total] = $this->findChildren($id, $relation, $whereNode, $whereEdge, $limit, $offset);
        } elseif ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::BELONGS_TO_MANY) {
            [$edges, $total] = $this->findParents($id, $relation, $whereNode, $whereEdge, $limit, $offset);
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
        string $orderBy = '`e`.`created_at` ASC'
    ): array
    {
        [$sql, $parameters] = $this->buildChildFindSql($parentId, $relation->name, $whereNode, $whereEdge);
        $order = 'ORDER BY ' . $orderBy . ' LIMIT ' . $offset . ', ' . $limit;

        //print_r(['SELECT `e`.`child_id` ' . $sql . ' GROUP BY `e`.`child_id`',$parameters]);

        $statement = $this->statement('SELECT `e`.`child_id` ' . $sql . ' GROUP BY `e`.`child_id` ' /* . $order */);

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
        return [$result, $total];
    }

    protected function buildChildFindSql(string $parentId, string $childName, ?array $whereNode, ?array $whereEdge)
    {
        $joins = [];
        $whereSql = ' WHERE `e`.`parent_id` = :parentId AND `e`.`child` = :child AND `e`.`deleted_at` IS NULL ';
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

        $sql = 'FROM `edge` AS `e` LEFT JOIN `node` AS `n`ON `n`.`id` = `e`.`child_id` ' . implode(' ', $joins) . $whereSql;

        return [$sql, $parameters];
    }

    protected function findParents(
        string $childId,
        Relation $relation,
        ?array $whereNode,
        ?array $whereEdge,
        int $limit = 10,
        int $offset = 0,
        string $orderBy = '`e`.`created_at` ASC'
    ): array
    {
        [$sql, $parameters] = $this->buildParentFindSql($childId, $relation->name, $whereNode, $whereEdge);
        $order = 'ORDER BY ' . $orderBy . ' LIMIT ' . $offset . ', ' . $limit;

        //print_r(['SELECT `e`.`parent_id` ' . $sql . ' GROUP BY `e`.`parent_id`',$parameters]);

        $statement = $this->statement('SELECT `e`.`parent_id` ' . $sql . ' GROUP BY `e`.`parent_id` ' /* . $order */);

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

    protected function buildParentFindSql(string $childId, string $parentName, ?array $whereNode, ?array $whereEdge)
    {
        $joins = [];
        $whereSql = ' WHERE `e`.`child_id` = :childId AND `e`.`parent` = :parent AND `e`.`deleted_at` IS NULL ';
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

        $sql = 'FROM `edge` AS `e` LEFT JOIN `node` AS `n`ON `n`.`id` = `e`.`parent_id` ' . implode(' ', $joins) . $whereSql;

        return [$sql, $parameters];
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

    /*
    protected function findParents(
        string $childId,
        Relation $relation,
        ?array $whereNode,
        ?array $whereEdge,
        int $limit = 10,
        int $offset = 0,
        string $orderBy = '`n`.`created_at` ASC'
    ): array
    {
        $result = [];
        $parentName = $relation->name;
        foreach($this->fetchParentEdges($childId, $parentName) as $edge) {
            $properties = $this->convertProperties($this->fetchEdgeProperties($childId, $edge->parent_id), $relation);
            $row = [];
            foreach ($properties as $key => $value) {
                $row[$key] = $value;
            }
            $row['_node'] = $this->load($parentName, $edge->parent_id);
            $result[] = $row;
        }
        return $result;
    }
    protected function fetchParentEdges(
        string $childId,
        string $parentName,
    ): array
    {
        $sql = 'SELECT * FROM `edge` WHERE parent = :parent AND child_id = :id';
        $statement = $this->statement($sql);
        $statement->execute(
            [
                ':id'    => $childId,
                ':parent' => $parentName
            ]
        );
        return $statement->fetchAll(PDO::FETCH_OBJ);
    }*/

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

    protected function insertEdge(string $parentId, string $childId, string $parent, string $child): bool
    {
        $sql = 'INSERT INTO `edge` (`parent_id`, `child_id`, `parent`, `child`) VALUES 
                    (:parent_id, :child_id, :parent, :child)';
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


    protected function updateNode(string $id): bool
    {
        $sql = 'UPDATE node SET updated_at = now() WHERE id = :id';
        $statement = $this->statement($sql);
        return $statement->execute([':id' => $id]);
    }

    protected function deleteNodeProperty(string $nodeId, string $propertyName): bool
    {
        $sql = 'UPDATE `node_property` SET deleted_at = now() '
            . 'WHERE deleted_at IS NULL AND `node_id` = :node_id AND `property` = :property';
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
        //\App\Timer::start(__METHOD__);
        $this->deleteNode($id);
        //\App\Timer::stop(__METHOD__);
        return $model;
    }

    protected function deleteNode(string $id): bool
    {
        $sql = 'UPDATE node SET deleted_at = now() WHERE id = :id';
        $statement = $this->statement($sql);
        return $statement->execute([':id' => $id]);
    }


}