<?php
declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\QueryBuilders;

use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Utils\JwtAuthentication;
use RuntimeException;

class MysqlQueryBuilder
{

    protected string $name;
    protected ?string $mode = null;
    protected array $columns;
    protected array $orderBys = [];
    protected string $resultType;
    protected Model $model;
    protected Relation $relation;
    protected string $limit = '';
    protected string $parameterPrefix = 'p';
    protected array $parameters = [];
    protected array $updateParameters = [];
    protected array $where = [];
    protected array $joins = [];
    protected string $groupBy = '';
    protected string $sql;


    protected function __construct(){}

    public static function forModel(Model $model, string $name, string $parameterPrefix = 'p'): MysqlQueryBuilder
    {
        $builder = new MysqlQueryBuilder();
        $builder->parameterPrefix = $parameterPrefix;
        $builder->name = 'node';
        $builder->where[] = $builder->fieldName('model') . ' = '. $builder->parameter($name);
        $builder->model = $model;
        $builder->resultType = ' AND ' . $builder->fieldName('deleted_at') . ' IS NULL';
        return $builder;
    }

    public static function forRelation(Relation $relation, array $parentIds): MysqlQueryBuilder
    {
        $builder = new MysqlQueryBuilder();
        $builder->name = 'edge';
        if ($relation->type === Relation::HAS_ONE || $relation->type === Relation::HAS_MANY) {
            $builder->where[] = $builder->fieldName('_parent_id') . ' IN '. $builder->parameterArray($parentIds);
            $builder->where[] = $builder->fieldName('_child') . ' = '. $builder->parameter($relation->name);
            $builder->joins[] = 'LEFT JOIN `node` ON `node`.`id` = ' . $builder->fieldName('_child_id');
        } elseif ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::BELONGS_TO_MANY) {
            $builder->where[] = $builder->fieldName('_child_id') . ' IN '. $builder->parameterArray($parentIds);
            $builder->where[] = $builder->fieldName('_parent') . ' = '. $builder->parameter($relation->name);
            $builder->joins[] = 'LEFT JOIN `node` ON `node`.`id` = ' . $builder->fieldName('_parent_id');
        } else {
            throw new RuntimeException('Unknown relation type: ' .  $relation->type);
        }
        $builder->relation = $relation;
        $classname = $relation->classname;
        $builder->model = new $classname();
        $builder->resultType = ' AND ' . $builder->fieldName('_deleted_at') . ' IS NULL';
        $builder->resultType .= ' AND ' . $builder->fieldName('deleted_at') . ' IS NULL';
        return $builder;
    }

    public function tenant(?string $tenantId): MysqlQueryBuilder
    {
        if ($tenantId !== null) {
            $this->where[] = '`node`.`tenant_id` = ' . $this->parameter($tenantId);
            if ($this->name === 'edge') {
                $this->where[] = '`edge`.`tenant_id` = ' . $this->parameter($tenantId);
            }
        }
        return $this;
    }


    public function select(array $fields): MysqlQueryBuilder
    {
        if ($this->mode !== null) {
            throw new \RuntimeException('Cannot do SELECT on a query that\' already set to ' . $this->mode);
        }
        $this->mode = 'SELECT';
        foreach ($fields as $field) {
            if (in_array($field, $this->getBaseColumns())) {
                $this->columns[] = $this->fieldName($field);
            } else {
                $join = $this->join($field);
                $this->columns[] = $join . '.`value_int`)';
                $this->columns[] = $join . '.`value_float`)';
                $this->columns[] = $join . '.`value_string`)';
            }
        }
        return $this;
    }

    public function delete(): MysqlQueryBuilder
    {
        if ($this->mode !== null) {
            throw new \RuntimeException('Cannot do DELETE on a query that\'s already set to ' . $this->mode);
        }
        $this->mode = 'DELETE';
        return $this;
    }

    public function update(array $fieldValues): MysqlQueryBuilder
    {
        if ($this->mode !== null) {
            throw new \RuntimeException('Cannot do UPDATE on a query that\'s already set to ' . $this->mode);
        }
        $this->mode = 'UPDATE';
        $this->columns[] = $this->fieldName('updated_at') . ' = now()';
        foreach ($fieldValues as $field => $value) {
            if (str_starts_with($field, '_')) {
                continue;
            }
            if (in_array($field, $this->getBaseColumns())) {
                $this->columns[] = $this->fieldName($field) . ' = ' . $this->updateParameter($value);
            } else {
                $join = $this->join($field);
                if (is_int($value)) {
                    $this->columns[] = $join . '.`value_int` = ' . $this->updateParameter($value);
                } elseif (is_float($value)) {
                    $this->columns[] = $join . '.`value_float` = ' . $this->updateParameter($value);
                } elseif (is_string($value)) {
                    $this->columns[] = $join . '.`value_string` = ' . $this->updateParameter($value);
                }
            }
        }
        return $this;
    }

    public function selectMax(string $field, string $alias, string $valueType = 'value_int'): MysqlQueryBuilder
    {
        $this->mode = 'SELECT';
        if (in_array($field, $this->getBaseColumns())) {
            $this->columns[] = 'max(' . $this->fieldName($field) . ') AS `' . $alias . '`';
        } else {
            $join = $this->join($field);
            $this->columns[] = 'max(' . $join . '.`' . $valueType . '`) AS `' . $alias . '`';
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
        if ($this->mode === 'edge') {
            $this->resultType .= ' AND ' . $this->fieldName('_deleted_at') . ' IS NOT NULL';
        }
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

    public function whereHas(Model $model, string $name, string $relationType, ?array $where): MysqlQueryBuilder
    {
        if ($where === null || count($where) === 0) {
            return $this;
        }
        $query = self::forModel($model, $name, $name)
            ->select(['id'])
            ->where($where);

        $as = '`' . $name.'Edge`';
        $joinSql = ' LEFT JOIN `edge` AS ' . $as . ' ON (';
        $whereSql = $as;
        if ($relationType === Relation::HAS_ONE || $relationType === Relation::HAS_MANY) {
            $joinSql .= $as.'.`parent_id` = `node`.`id` AND '. $as . '.`child` = ';
            $whereSql .= '.`child_id` IN (';
        } elseif ($relationType === Relation::BELONGS_TO || $relationType === Relation::BELONGS_TO_MANY) {
            $joinSql .= $as.'.`child_id` = `node`.`id` AND '. $as . '.`parent` = ';
            $whereSql .= '.`parent` IN (';
        } else {
            throw new RuntimeException('Unknown relation type: ' .  $relationType);
        }
        $joinSql .= $this->parameter($name). ')';
        $whereSql .= $query->toSql() . ')';

        $this->joins[] = $joinSql;
        $this->where[] = $whereSql;
        $this->parameters = array_merge($this->parameters, $query->getParameters());

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

        $this->groupBy = match($this->name) {
                        'node' => ' GROUP BY ' . $this->fieldName('id') . ' ',
                        'edge' => ' GROUP BY ' . $this->fieldName('_parent_id') . ', ' . $this->fieldName('_child_id') . ' '
                    };
        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getUpdateParameters(): array
    {
        return array_merge($this->parameters, $this->updateParameters);
    }

    public function toSql(): string
    {
        return match($this->mode) {
            'SELECT' => $this->toSelectSql(),
            'UPDATE' => $this->toUpdateSql(),
            'DELETE' => $this->toDeleteSql()
        };
    }

    protected function toSelectSql(): string
    {
        sort($this->columns); // sort to optimize statement re-use
        $sql = 'SELECT ' . implode(', ', $this->columns) . ' ' . $this->createSql();
        $sql .= $this->groupBy;
        if (count($this->orderBys) > 0) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }
        $sql .= ' ' . $this->limit;
        return $sql;
    }

    protected function toDeleteSql(): string
    {
        throw new RuntimeException('not implemented yet'); // TODO
    }

    protected function toUpdateSql(): string
    {
        $sql = 'UPDATE `' . $this->name . '` ';
        $sql .= implode(' ', $this->joins);
        $sql .= ' SET ' . implode(', ', $this->columns);
        $sql .= ' WHERE ' . implode(' AND ', $this->where);
        return $sql;
    }


    public function toCountSql(): string
    {
        return 'SELECT count(*) ' . $this->createSql();
    }

    protected function createSql(): string
    {
        if (!isset($this->sql)) {
            sort($this->where); // sort to optimize statement re-use
            $this->sql = 'FROM `' . $this->name . '` ';
            $this->sql .= implode(' ', $this->joins);
            $this->sql .= ' WHERE ' . implode(' AND ', $this->where);
            $this->sql .= $this->resultType;
        }
        return $this->sql;
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
            if (isset($where['column'])) {
                if (in_array($where['column'], $this->getBaseColumns())) {
                    $sql = $this->fieldName($where['column']);
                } else {
                    $sql = $this->join($where['column']);
                    $sql .= '.' . $this->getFieldType($where['column']);
                }
                $sql .= ' ' . $where['operator'] ?? '=';
                if ($where['operator'] !== 'IS NULL' && $where['operator'] !== 'IS NOT NULL') {
                    if ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                        if (!is_array($where['value'])) {
                            throw new RuntimeException($where['operator']. ' requires the value to be an array.');
                        }
                        $sql .= ' ' . $this->parameterArray($where['value']);
                    } elseif ($where['operator'] === 'BETWEEN' || $where['operator'] === 'NOT BETWEEN') {
                        if (!is_array($where['value'])) {
                            throw new RuntimeException($where['operator']. ' requires the value to be an array.');
                        }
                        $sql .= ' ' . $this->parameter($where['value'][0]) . ' AND ' . $this->parameter($where['value'][1]);
                    } else {
                        $sql .= ' ' . $this->parameter($where['value']);
                    }
                }
                $sqls[] = $sql;
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

    protected function getFieldType(string $property): string
    {
        if (str_starts_with($property, '_')) {
            $key = substr($property, 1);
            $field = $this->relation->$key;
        } else {
            $field = $this->model->$property;
        }
        return match($field->type) {
            Type::BOOLEAN, Type::INT, Field::TIME, Field::DATE_TIME, Field::DATE, Field::TIMEZONE_OFFSET, Field::DECIMAL => '`value_int`',
            Type::FLOAT => '`value_float`',
            default => '`value_string`'
        };
    }

    protected function getBaseColumns(): array
    {
        return match($this->name) {
            'node' => ['created_at', 'updated_at', 'deleted_at', 'id'],
            'edge' => ['created_at', 'updated_at', 'deleted_at', 'id', '_parent_id', '_child_id', '_parent', '_child', '_created_at', '_updated_at', '_deleted_at']
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

    protected function parameter($value): string
    {
        $key = ':' . $this->parameterPrefix . count($this->parameters);
        $this->parameters[$key] = $value;
        return $key;
    }

    protected function parameterArray(array $array): string
    {
        $params = [];
        foreach ($array as $value) {
            $params[] = $this->parameter($value);
        }
        return '(' . implode(',', $params) . ')';
    }

    protected function updateParameter($value) {
        $key = ':u' . count($this->updateParameters);
        $this->updateParameters[$key] = $value;
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