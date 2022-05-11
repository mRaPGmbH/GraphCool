<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use GraphQL\Error\Error;
use RuntimeException;

class MysqlFlatQueryBuilder
{

    protected string $table;
    protected ?string $tenantId = null;
    protected ?string $mode = null;
    /** @var string[] */
    protected array $where = [];
    /** @var mixed[] */
    protected array $parameters = [];
    /** @var mixed[] */
    protected array $updateParameters = [];
    /** @var string[] */
    protected array $columns;
    /** @var string[] */
    protected array $orderBys = [];
    protected string $limit = '';
    protected string $sql;



    public static function forTable(string $table): MysqlFlatQueryBuilder
    {
        $builder = new MysqlFlatQueryBuilder();
        $builder->table = $table;
        return $builder;
    }

    public function tenant(?string $tenantId): MysqlFlatQueryBuilder
    {
        if ($tenantId !== null) {
            $this->tenantId = $tenantId;
            $this->where[] = $this->fieldName('tenant_id') . ' = ' . $this->parameter($tenantId);
        }
        return $this;
    }

    protected function fieldName(string $field): string
    {
        return '`' . $this->table . '`.`' . $field . '`';
    }

    protected function parameter(mixed $value): string
    {
        $key = ':p' . count($this->parameters);
        $this->parameters[$key] = $value;
        return $key;
    }

    protected function updateParameter(mixed $value): string
    {
        $key = ':u' . count($this->updateParameters);
        $this->updateParameters[$key] = $value;
        return $key;
    }

    public function delete(): MysqlFlatQueryBuilder
    {
        if ($this->mode !== null && $this->mode !== 'DELETE') {
            throw new RuntimeException('Cannot do DELETE on a query that\'s already set to ' . $this->mode);
        }
        $this->mode = 'DELETE';
        return $this;
    }

    public function update(array $fieldValues): MysqlFlatQueryBuilder
    {
        if ($this->mode !== null && $this->mode !== 'UPDATE') {
            throw new RuntimeException('Cannot do UPDATE on a query that\'s already set to ' . $this->mode);
        }
        $this->mode = 'UPDATE';
        $this->columns[] = $this->fieldName('updated_at') . ' = now()';
        foreach ($fieldValues as $field => $value) {
            $this->columns[] = $this->fieldName($field) . ' = ' . $this->updateParameter($value);
        }
        return $this;
    }

    public function selectMax(string $field, string $alias): MysqlFlatQueryBuilder
    {
        if ($this->mode !== null && $this->mode !== 'SELECT') {
            throw new RuntimeException('Cannot do UPDATE on a query that\'s already set to ' . $this->mode);
        }
        $this->mode = 'SELECT';
        $this->columns[] = 'max(' . $this->fieldName($field) . ') AS `' . $alias . '`';
        return $this;
    }

    public function selectSum(string $field, string $alias, string $valueType = 'value_int'): MysqlFlatQueryBuilder
    {
        if ($this->mode !== null && $this->mode !== 'SELECT') {
            throw new RuntimeException('Cannot do UPDATE on a query that\'s already set to ' . $this->mode);
        }
        $this->mode = 'SELECT';
        $this->columns[] = 'sum(' . $this->fieldName($field) . ') AS `' . $alias . '`';
        return $this;
    }

    public function orderBy(array $orderBys): MysqlFlatQueryBuilder
    {
        foreach ($orderBys as $orderBy) {
            $field = $orderBy['field'] ?? 'created_at';
            $order = $orderBy['order'] ?? 'ASC';
            if ($order === 'RAND') {
                $this->orderBys[] = 'rand()';
            } else {
                $this->orderBys[] = $this->fieldName($field) . ' ' . $order;
            }
        }
        return $this;
    }

    public function limit(?int $limit, ?int $offset): MysqlFlatQueryBuilder
    {
        $this->limit = ' LIMIT ' . ($offset ?? 0) . ', ' . ($limit ?? 10);
        return $this;
    }

    public function select(array $fields): MysqlFlatQueryBuilder
    {
        if ($this->mode !== null && $this->mode !== 'SELECT') {
            throw new RuntimeException('Cannot do SELECT on a query that\' already set to ' . $this->mode);
        }
        $this->mode = 'SELECT';
        foreach ($fields as $field) {
            $this->columns[] = $this->fieldName($field);
        }
        return $this;
    }

    public function toSql(): string
    {
        return match ($this->mode) {
            default => $this->toSelectSql(),
            'UPDATE' => $this->toUpdateSql(),
            'DELETE' => $this->toDeleteSql()
        };
    }

    protected function toSelectSql(): string
    {
        if (empty($this->columns)) {
            $sql = 'SELECT * ' . $this->createSql();
        } else {
            sort($this->columns); // sort to optimize statement re-use
            $sql = 'SELECT ' . implode(', ', $this->columns) . ' ' . $this->createSql();
        }
        //$sql .= $this->groupBy;
        if (count($this->orderBys) > 0) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }
        $sql .= ' ' . $this->limit;
        return $sql;
    }

    protected function createSql(): string
    {
        if (!isset($this->sql)) {
            sort($this->where); // sort to optimize statement re-use
            $this->sql = 'FROM `' . $this->table . '` ';
            //$this->sql .= implode(' ', $this->joins);
            $this->sql .= ' WHERE ' . implode(' AND ', $this->where);
        }
        return $this->sql;
    }

    protected function toUpdateSql(): string
    {
        $sql = 'UPDATE `' . $this->table . '` ';
        //$sql .= implode(' ', $this->joins);
        $sql .= ' SET ' . implode(', ', $this->columns);
        $sql .= ' WHERE ' . implode(' AND ', $this->where);
        return $sql;
    }

    protected function toDeleteSql(): string
    {
        throw new RuntimeException('not implemented yet'); // TODO
    }


    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getUpdateParameters(): array
    {
        return array_merge($this->parameters, $this->updateParameters);
    }

    public function toCountSql(): string
    {
        return 'SELECT count(DISTINCT ' . $this->fieldName('id') . ') ' . $this->createSql();
    }

    public function where(?array $where): MysqlFlatQueryBuilder
    {
        if ($where === null || count($where) === 0) {
            return $this;
        }
        $whereSql = $this->whereRecursive($where);
        if ($whereSql !== null) {
            $this->where[] = $whereSql;
        }
        return $this;
    }

    protected function whereRecursive(array $wheres, string $mode = 'AND'): ?string
    {
        if (
            isset($wheres['operator'])
            || isset($wheres['value'])
            || isset($wheres['column'])
            || isset($wheres['AND'])
            || isset($wheres['OR'])
            || isset($wheres['fulltextSearch'])
        ) {
            $wheres = [$wheres];
        }
        $sqls = [];
        foreach ($wheres as $where) {
            if (isset($where['column'])) {
                $sqls[] = $this->resolveSingleWhere($where);
            }
            /*
            if (isset($where['fulltextSearch'])) {
                $sqls[] = $this->resolveFulltextWhere($where['fulltextSearch']);
            }*/
            if (isset($where['OR'])) {
                $sql = $this->whereRecursive($where['OR'], 'OR');
                if ($sql !== null) {
                    $sqls[] = '(' . $sql . ')';
                }
            }
            if (isset($where['AND'])) {
                $sql = $this->whereRecursive($where['AND'], 'AND');
                if ($sql !== null) {
                    $sqls[] = '(' . $sql . ')';
                }
            }
        }
        if (count($sqls) === 0) {
            return null;
        }
        return implode(' ' . $mode . ' ', $sqls);
    }

    /**
     * @throws Error
     */
    protected function resolveSingleWhere(array $where): string
    {
        $sql = $this->fieldName($where['column']);
        $sql .= ' ' . $where['operator'] ?? '=';
        if ($where['operator'] !== 'IS NULL' && $where['operator'] !== 'IS NOT NULL') {
            if ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                if (!is_array($where['value'])) {
                    throw new Error($where['operator'] . ' requires the value to be an array.');
                }
                if (count($where['value']) === 0) {
                    return '0=1'; // TODO: throw error. this is now only rigged to not find anything, because Kassa can't handle Errors
                }
                $sql .= ' ' . $this->parameterArray($where['value']);
            } elseif ($where['operator'] === 'BETWEEN' || $where['operator'] === 'NOT BETWEEN') {
                $values = $where['value'] ?? null;
                if (!is_array($values) || count($values) <= 1) {
                    throw new Error($where['operator'] . ' requires the value to be an array of 2 values.');
                }
                $values = array_values($values);
                $sql .= ' ' . $this->parameter($values[0]) . ' AND ' . $this->parameter($values[1]);
            } else {
                if (!array_key_exists('value', $where)) {
                    throw new Error($where['operator'] . ' requires a value');
                }
                $sql .= ' ' . $this->parameter($where['value'] ?? null);
            }
        }
        return $sql;
    }

    protected function parameterArray(array $array): string
    {
        $params = [];
        foreach ($array as $value) {
            $params[] = $this->parameter($value);
        }
        return '(' . implode(',', $params) . ')';
    }



}