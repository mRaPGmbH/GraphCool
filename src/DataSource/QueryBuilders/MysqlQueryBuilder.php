<?php
declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\QueryBuilders;

use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use RuntimeException;

class MysqlQueryBuilder
{

    protected string $name;
    protected array $selects;
    protected array $orderBys = [];
    protected string $resultType;
    protected Model|Relation $base;
    protected string $limit = '';
    protected array $parameters = [];
    protected array $where = [];
    protected array $joins = [];


    public function __construct(Model|Relation $base, string $name)
    {
        if ($base instanceof Model) {
            $this->name = 'node';
            $this->where[] = $this->fieldName('model') . ' = '. $this->parameter($name);
        } elseif ($base instanceof Relation) {
            $this->name = 'edge';
            if ($base->type === Relation::HAS_ONE || $base->type === Relation::HAS_MANY) {
                $this->where[] = $this->fieldName('_parent_id') . ' = '. $this->parameter($name);
                $this->where[] = $this->fieldName('_child') . ' = '. $this->parameter($base->name);
                $this->joins[] = 'LEFT JOIN `node` ON `node`.`id` = ' . $this->fieldName('_child_id');
            } elseif ($base->type === Relation::BELONGS_TO || $base->type === Relation::BELONGS_TO_MANY) {
                $this->where[] = $this->fieldName('_child_id') . ' = '. $this->parameter($name);
                $this->where[] = $this->fieldName('_parent') . ' = '. $this->parameter($base->name);
                $this->joins[] = 'LEFT JOIN `node` ON `node`.`id` = ' . $this->fieldName('_parent_id');
            } else {
                throw new RuntimeException('Unknown relation type: ' .  $base->type);
            }
        } else {
            throw new RuntimeException('Base must be a Model or Relation, but got ' . get_class($base) . ' instead.');
        }
        $this->base = $base;
        $this->resultType = ' AND ' . $this->fieldName('deleted_at') . ' IS NULL';
    }

    public function select(array $fields): MysqlQueryBuilder
    {
        foreach ($fields as $field) {
            $this->selects[] = $this->fieldName($field);
        }
        return $this;
    }

    public function orderBy(array $orderBys): MysqlQueryBuilder
    {
        foreach ($orderBys as $orderBy) {
            $field = $orderBy['field'] ?? 'created_at';
            $order = $orderBy['order'] ?? 'ASC';
            if (in_array($field, $this->getBaseColumns())) {
                $this->orderBys[] = $this->fieldName($field) . ' ' . $order;
            } else {
                $join = $this->join($field);
                $this->orderBys[] = $join . '.`value_int` ' . $order;
                $this->orderBys[] = $join . '.`value_float` ' . $order;
                $this->orderBys[] = $join . '.`value_string` ' . $order;
            }
        }
        return $this;
    }

    public function limit(?int $limit, ?int $offset): MysqlQueryBuilder
    {
        $this->limit = ' LIMIT ' . ($offset ?? 0) . ', ' . ($limit ?? 10);
        return $this;
    }

    public function withTrashed(): MysqlQueryBuilder
    {
        $this->resultType = '';
        return $this;
    }

    public function onlySoftDeleted(): MysqlQueryBuilder
    {
        $this->resultType = ' AND ' . $this->fieldName('deleted_at') . ' IS NOT NULL';
        return $this;
    }

    public function where(?array $where): MysqlQueryBuilder
    {
        if ($where === null || count($where) === 0) {
            return $this;
        }
        $this->where[] = $this->whereRecursive($where);
        return $this;
    }

    public function whereRelated(?array $where): MysqlQueryBuilder
    {
        if ($this->name !== 'edge') {
            throw new RuntimeException(__METHOD__ . ' can only be used on edges.' . $this->name);
        }
        if ($where === null || count($where) === 0) {
            return $this;
        }
        $this->where[] = $this->whereRecursive($where);
        return $this;
    }

    public function search(?string $value): MysqlQueryBuilder
    {
        if ($value !== null) {
            $join = $this->join(null);
            $this->where[] = $join . '.`value_string` LIKE ' . $this->parameter('%' . str_replace(' ','%' ,$value) . '%');
        }
        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function toSql(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->selects) . ' ' . $this->createSql();
        if (count($this->orderBys) > 0) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }
        $sql .= ' ' . $this->limit;
        return $sql;
    }

    public function toCountSql(): string
    {
        return 'SELECT count(*) ' . $this->createSql();
    }

    protected function createSql(): string
    {
        sort($this->where); // sort to optimize statement re-use
        sort($this->selects);

        $sql = 'FROM `' . $this->name . '` ';
        $sql .= implode(' ', $this->joins);
        $sql .= ' WHERE ' . implode(' AND ', $this->where);
        $sql .= $this->resultType;
        return $sql;
    }

    protected function whereRecursive(array $wheres, string $mode = 'AND'): string
    {
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
                if (in_array($where['column'], $this->getBaseColumns())) {
                    if (isset($where['value'])) {
                        $sqls[] = $this->fieldName($where['column']) . ' ' . $where['operator'] . ' ' . $this->parameter($where['value']);
                    } else {
                        $sqls[] = $this->fieldName($where['column']) . ' ' . $where['operator'];
                    }
                } else {
                    $join = $this->join($where['column']);
                    if (is_bool($where['value']) || is_int($where['value'])) {
                        $sqls[] = $join . '.`value_int` ' . $where['operator'] . ' ' . $this->parameter((int)$where['value']);
                    } elseif (is_float($where['value'])) {
                        $sqls[] = $join . '.`value_float` ' . $where['operator'] . ' ' . $this->parameter((float)$where['value']);
                    } else {
                        $sqls[] = $join . '.`value_string` ' . $where['operator'] . ' ' . $this->parameter((string)$where['value']);
                    }
                }
            }
            if (isset($where['OR'])) {
                $sqls[] = '(' . $this->whereRecursive($where['OR'], 'OR') .')';
            }
            if (isset($where['AND'])) {
                $sqls[] = '(' . $this->whereRecursive($where['AND'], 'AND') .')';
            }
        }
        return implode(' ' . $mode . ' ', $sqls);
    }

    protected function getBaseColumns(): array
    {
        return match($this->name) {
            'node' => ['created_at', 'updated_at', 'deleted_at', 'id'],
            'edge' => ['created_at', 'updated_at', 'deleted_at', 'id', '_created_at', '_updated_at', '_deleted_at']
        };
    }

    protected function join(?string $property)
    {
        if (is_null($property)) {
            $name = '`search`';
            $base = 'node';
        } else {
            if (str_starts_with($property, '_')) {
                $name = '`edge' . $property . '`';
                $base = 'edge';
            } else {
                $name = '`node_' . $property . '`';
                $base = 'node';
            }
        }
        if (!isset($this->joins[$name])) {
            $join = 'LEFT JOIN `' . $base . '_property` AS ' . $name . ' ON (';
            $join .= match($base) {
                'node' => $name . '.`node_id` = `node`.`id`',
                'edge' => $name . '.`parent_id` = `edge`.`parent_id` AND ' . $name . '.`child_id` = `edge`.`child_id` '
            };
            if (!is_null($property)) {
                $join .= ' AND ' . $name . '.`property` = ' . $this->parameter($property);
            }
            $join .= ' AND ' . $name . '.`deleted_at` IS NULL)';
            $this->joins[$name] = $join;
        }
        return $name;
    }

    protected function parameter($value)
    {
        $key = ':p' . count($this->parameters);
        $this->parameters[$key] = $value;
        return $key;
    }

    protected function fieldName(string $field): string
    {
        if (str_starts_with($field, '_')) {
            $field = substr($field, 1);
            $name = 'edge';
        } else {
            $name = 'node';
        }
        if ($field === '*') {
            return '`' . $name . '`.*';
        }
        return '`' . $name . '`.`' . $field . '`';
    }

}