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
    protected array $groupBys = [];
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
                $this->where[] = $this->fieldName('parent_id') . ' = '. $this->parameter($name);
                $this->where[] = $this->fieldName('child') . ' = '. $this->parameter($base->name);
            } elseif ($base->type === Relation::BELONGS_TO || $base->type === Relation::BELONGS_TO_MANY) {
                $this->where[] = $this->fieldName('child_id') . ' = '. $this->parameter($name);
                $this->where[] = $this->fieldName('parent') . ' = '. $this->parameter($base->name);
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

    public function groupBy(array $groupBys): MysqlQueryBuilder
    {
        foreach ($groupBys as $groupBy) {
            $this->groupBys[] = $this->fieldName($groupBy);
        }
        return $this;
    }

    public function orderBy(array $orderBys): MysqlQueryBuilder
    {
        foreach ($orderBys as $orderBy) {
            $this->orderBys[] = $this->fieldName($orderBy['field']). ' ' . $orderBy['order'];
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
        if ($where !== null) {
            $this->where[] = $this->whereRecursive($where);
        }
        return $this;
    }

    public function whereRelated(?array $where): MysqlQueryBuilder
    {
        if ($where !== null) {
            $this->where[] = $this->whereRecursive($where);
        }
        return $this;
    }

    public function search(?string $value): MysqlQueryBuilder
    {
        if ($value !== null) {
            $join = $this->join(null);
            $this->where[] = $join . ' LIKE ' . $this->parameter('%' . str_replace(' ','%' ,$value) . '%');
        }
        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function toSql(): string
    {
        return 'SELECT ' . implode(', ', $this->selects) . ' ' . $this->createSql();
    }

    public function toCountSql(): string
    {
        return 'SELECT count(*) ' . $this->createSql();
    }

    protected function createSql(): string
    {
        sort($this->where);
        sort($this->joins);
        sort($this->selects);

        $sql = 'FROM `' . $this->name . '` ';
        $sql .= implode(' ', $this->joins);
        $sql .= ' WHERE ' . implode(' AND ', $this->where);
        if (count($this->groupBys) > 0) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBys);
        }
        if (count($this->orderBys) > 0) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }
        $sql .= ' ' . $this->limit;
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
                if (in_array($where['column'], ['created_at', 'updated_at', 'deleted_at', 'id'])) {
                    $sqls[] = $this->fieldName($where['column']) . ' ' . $where['operator'] . ' ' . $this->parameter($where['value']);
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

    protected function join(?string $property)
    {
        if (is_null($property)) {
            $name = '`search`';
        } else {
            $name = '`join_' . $property . '`';
        }
        if (!isset($this->joins[$name])) {
            $join = 'LEFT JOIN `' . $this->name . '_property` AS ' . $name . ' ON (';
            if ($this->name === 'node') {
                $join .= $name . '.`node_id` = `node`.`id`';
            }
            if (!is_null($property)) {
                $join .= ' AND ' . $name . '.`property` = ' . $this->parameter($property);
            }
            $join .= ' AND ' . $name . '.`deleted_at` IS NULL)';
            $this->joins[] = $join;
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
        if ($field === '*') {
            return '`' . $this->name . '`.*';
        }
        return '`' . $this->name . '`.`' . $field . '`';
    }

}