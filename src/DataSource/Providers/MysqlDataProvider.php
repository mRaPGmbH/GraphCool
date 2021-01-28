<?php


namespace Mrap\GraphCool\DataSource\Providers;

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

    public function findAll(string $name, array $args): ?array
    {
        $limit = $args['first'] ?? 10;
        $page = $args['page'] ?? 0;
        $offset = $page * $limit;
        $where = $args['where'] ?? null;
        [$ids, $total] = $this->findNodes($name, $where, $limit, $offset);
        $paginatorInfo = new stdClass();
        $paginatorInfo->count = count($ids);
        $paginatorInfo->currentPage = $args['page'] ?? 0;
        $paginatorInfo->firstItem = 1;
        $paginatorInfo->hasMorePages = $total > ($page + 1) * $limit;
        $paginatorInfo->lastItem = $total;
        $paginatorInfo->lastPage = round($total / $limit);
        $paginatorInfo->perPage = $limit;
        $paginatorInfo->total = $total;
        return [
            $this->loadAll($name, $ids),
            $paginatorInfo
        ];
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
            $field = $model->$key;
            $node->$key = $field->convert($property->value_string);
        }
        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $node->$key = function () use ($id, $relation) {
                $result = $this->getRelatedNodes($id, $relation);
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
                $this->insertEdge($value, $id, $item->classname, $name);
            } elseif ($item instanceof Field) {
                if (!isset($data[$key])) {
                    continue;
                }
                $value = $data[$key];
                if ($value === null) {
                    continue;
                }
                $this->insertOrUpdateNodeProperty($id, $name, $key, null, $item->convertBack($value));
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
            $this->insertOrUpdateNodeProperty($data['id'], $name, $key, null, $item->convertBack($value));
        }
        //\App\Timer::stop(__METHOD__);
        return $this->load($name, $data['id']);
    }

    public function delete(string $name, string $id): stdClass
    {
        $model = $this->load($name, $id);
        //\App\Timer::start(__METHOD__);
        $this->deleteNode($id);
        //\App\Timer::stop(__METHOD__);
        return $model;
    }

    protected function getModel(string $name): Model
    {
        $classname = 'App\\Models\\' . $name;
        return new $classname();
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

    protected function fetchNodeProperties(string $id): array
    {
        $sql = 'SELECT * FROM `node_property` WHERE `node_id` = :node_id';
        $statement = $this->statement($sql);
        $statement->execute([':node_id' => $id]);
        return $statement->fetchAll(PDO::FETCH_OBJ);
    }

    protected function getRelatedNodes(string $id, Relation $relation): ?array
    {
        $related_ids = [];
        if ($relation->type === Relation::HAS_MANY || $relation->type === Relation::HAS_ONE) {
            $related_ids = $this->getChildrenIds($id, $relation->name);
        } elseif ($relation->type === Relation::BELONGS_TO) {
            $related_ids = $this->getParentIds($id, $relation->name);
        }
        if (count($related_ids) === 0) {
            return null;
        }
        return $this->loadAll($relation->name, $related_ids);
    }

    protected function getChildrenIds(string $parent_id, string $childName): array
    {
        $sql = 'SELECT `child_id` FROM `edge` WHERE child = :child AND parent_id = :id';
        $statement = $this->statement($sql);
        $statement->execute(
            [
                ':id'    => $parent_id,
                ':child' => $childName
            ]
        );
        $result = [];
        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $relation) {
            $result[] = $relation->child_id;
        }
        return $result;
    }

    protected function getParentIds(string $child_id, string $parentName): array
    {
        $sql = 'SELECT `parent_id` FROM `edge` WHERE parent = :parent AND child_id = :id';
        $statement = $this->statement($sql);
        $statement->execute(
            [
                ':id'     => $child_id,
                ':parent' => $parentName
            ]
        );
        $result = [];
        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $relation) {
            $result[] = $relation->parent_id;
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

    protected function insertOrUpdateNodeProperty(
        string $nodeId,
        string $name,
        string $propertyName,
        ?int $valueInt,
        ?string $valueString
    ): bool {
        $sql = 'INSERT INTO `node_property` (`node_id`, `model`, `property`, `value_int`, `value_string`) '
            . 'VALUES (:node_id, :model, :property, :value_int, :value_string) '
            . 'ON DUPLICATE KEY UPDATE `value_int` = :value_int2, `value_string` = :value_string2, `deleted_at` = NULL';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':node_id'      => $nodeId,
                ':model'        => $name,
                ':property'     => $propertyName,
                ':value_int'    => $valueInt,
                ':value_string' => $valueString,
                ':value_int2'    => $valueInt,
                ':value_string2' => $valueString
            ]
        );
    }

    protected function deleteNodeProperty(string $nodeId, string $propertyName): bool
    {
        $sql = 'UPDATE `node_property` SET deleted_at = now() '
            . 'WHERE deleted_at IS NULL AND `node_id` = :node_id AND `property` = :property';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':node_id'      => $nodeId,
                ':property'     => $propertyName,
            ]
        );
    }

    protected function updateNode(string $id): bool
    {
        $sql = 'UPDATE node SET updated_at = now() WHERE id = :id';
        $statement = $this->statement($sql);
        return $statement->execute([':id' => $id]);
    }

    protected function deleteNode(string $id): bool
    {
        $sql = 'UPDATE node SET deleted_at = now() WHERE id = :id';
        $statement = $this->statement($sql);
        return $statement->execute([':id' => $id]);
    }

    protected function insertEdge(string $parentId, string $childId, string $parent, string $child): bool
    {
        $sql = 'INSERT INTO `edge` (`parent_id`, `child_id`, `parent`, `child`) VALUES 
                    (:parent_id, :child_id, :parent, :child)';
        $statement = $this->statement($sql);
        return $statement->execute(
            [
                ':parent_id' => $parentId,
                ':child_id' => $childId,
                ':parent' => $parent,
                ':child' => $child,
            ]
        );
    }

    protected function findNodes(string $name, array $where, int $limit = 10, int $offset = 0, string $orderBy = 'created_at ASC'): array
    {
        $sql = 'SELECT `id` FROM `node` WHERE `model` = :model ORDER BY ' . $orderBy . ' LIMIT ' . $offset . ', ' . $limit;
        $statement = $this->statement($sql);
        $statement->execute([
            ':model' => $name
        ]);
        $result = [];
        foreach ($statement->fetchAll(PDO::FETCH_OBJ) as $row) {
            $result[] = $row->id;
        }
        $sql = 'SELECT count(*) FROM `node` WHERE model = :model';
        $statement = $this->statement($sql);
        $statement->execute([
            ':model' => $name
        ]);
        $total = (int) $statement->fetchColumn();
        return [$result, $total];
    }

    protected function buildWhereSqlPart(array $where, int $i = 0): array
    {
        $p = ':p'.$i;
        $sql = '`' . $where['column'] . '` ' . $where['operator'] . ' ' . $p;
        $params = [
            $p => $where['value']
        ];
        return [$sql, $params];
    }


}