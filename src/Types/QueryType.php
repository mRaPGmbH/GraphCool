<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlQueryBuilder;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\ModelQuery;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\FileImport2;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\TimeZone;
use ReflectionClass;
use RuntimeException;
use stdClass;
use function Mrap\GraphCool\model;

class QueryType extends BaseType
{

    /** @var callable[] */
    protected array $customResolvers = [];

    protected array $queries = [];

    public function __construct()
    {
        $fields = [
            '_classDiagram' => Type::string(),
            '_ImportJob' => $this->job('Import'),
            '_ImportJobs' => $this->jobs('Import'),
            '_ExportJob' => $this->job('Export'),
            '_ExportJobs' => $this->jobs('Export'),
            '_History' => $this->history(),
            '_Token' => $this->token(),
        ];
        $this->customResolvers['_classDiagram'] = function ($rootValue, $args, $context, $info) {
            return $this->getDiagram();
        };
        $this->customResolvers['_Token'] = function ($rootValue, $args, $context, $info) {
            $name = strtolower($args['endpoint']);
            $operation = $args['operation'];
            Authorization::authorize($operation, $name);
            return JwtAuthentication::createLocalToken([$name => [$operation]], JwtAuthentication::tenantId());
        };

        foreach (ClassFinder::models() as $name => $classname) {
            $model = new $classname();
            //$fields[lcfirst($name)] = $this->read($name);
            //$fields[lcfirst($name) . 's'] = $this->list($name, $model);
            $fields['export' . $name . 's'] = $this->export($name, $model);
            $fields['import' . $name . 'sPreview'] = $this->previewImport($name);
        }

        foreach (ClassFinder::queries() as $name => $classname) {
            if ((new ReflectionClass($classname))->isSubclassOf(ModelQuery::class)) {
                foreach (ClassFinder::models() as $model => $tmp) {
                    $query = new $classname($model);
                    $this->queries[lcfirst($query->name)] = $query;
                }
            } else {
                $query = new $classname();
                $this->queries[$query->name] = $query;
            }
        }
        foreach ($this->queries as $name => $query) {
            $fields[$name] = $query->config;
        }

        ksort($fields);
        $config = [
            'name' => 'Query',
            'fields' => $fields,
            'resolveField' => function ($rootValue, $args, $context, $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    protected function token(): array
    {
        return [
            'type' => Type::nonNull(Type::string()),
            'description' => 'Get a single use JWT for a specific endpoint of this service.',
            'args' => [
                'endpoint' => Type::get('_Entity'),
                'operation' => Type::get('_Permission'),
            ]
        ];
    }

    /**
     * @param string $name
     * @param Model $model
     * @return mixed[]
     */
    protected function export(string $name, Model $model): array
    {
        return [
            'type' => Type::get('_FileExport'),
            'description' => 'Export ' . $name . 's filtered by given where clauses as a spreadsheet file (XLSX, CSV or ODS).',
            'args' => $this->exportArgs($name, $model),
        ];
    }

    protected function previewImport(string $name): array
    {
        return [
            'type' => Type::get('_' . $name.'ImportPreview'),
            'description' => 'Get a preview of what an import of a list of ' .  $name . 's from a spreadsheet would result in. Does not actually modify any data.' ,
            'args' => $this->importArgs($name)
        ];
    }

    /**
     * @param mixed[] $rootValue
     * @param mixed[] $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     * @throws \GraphQL\Error\Error
     */
    protected function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        if (isset($args['_timezone'])) {
            TimeZone::set($args['_timezone']);
        }
        if (isset($this->queries[$info->fieldName])) {
            return $this->queries[$info->fieldName]->resolve($rootValue, $args, $context, $info);
        }
        if (isset($this->customResolvers[$info->fieldName])) {
            return $this->customResolvers[$info->fieldName]($rootValue, $args, $context, $info);
        }

        if (is_object($info->returnType)) {
            if (str_ends_with($info->returnType->name, '_JobPaginator')) {
                $name = substr($info->returnType->name,1,-13) . 'Job';
                Authorization::authorize('find', $name);
                return DB::findJobs(JwtAuthentication::tenantId(), $this->getWorkerForJob($name), $args);
            }
            if ($info->returnType->name === '_History_Paginator') {
                $name = '_History';
                Authorization::authorize('find', $name);
                return DB::findHistory(JwtAuthentication::tenantId(), $args);
            }
            if (str_ends_with($info->returnType->name, 'Paginator')) {
                $name = substr($info->returnType->name, 1, -9);
                Authorization::authorize('find', $name);
                return DB::findAll(JwtAuthentication::tenantId(), $name , $args);
            }
            $type = $args['type'] ?? 'xlsx';
            $args['first'] = 1048575; // max number of rows allowed in excel - 1 (for headers)
            if (str_ends_with($info->fieldName, 'Async') && str_starts_with($info->fieldName, 'export')) {
                $name = substr($info->fieldName, 6, -6);
                Authorization::authorize('export', $name);
                $data = [
                    'name' => $name,
                    'args' => $args,
                    'jwt' => File::getToken(),
                ];
                return DB::addJob(JwtAuthentication::tenantId(), 'exporter', $name, $data);
            }
            if ($info->returnType->name === '_FileExport') {
                $name = ucfirst(substr($info->fieldName, 6, -1));
                Authorization::authorize('export', $name);
                $data = DB::findAll(JwtAuthentication::tenantId(), $name, $args)->data;
                if ($data instanceof \Closure) {
                    $data = $data();
                }
                return File::write(
                    $name,
                    $data ?? [],
                    $args,
                    $type
                );
            }
            if (str_starts_with($info->fieldName, '_') && str_ends_with($info->fieldName, 'Job')) {
                $name = substr($info->fieldName, 1);
                Authorization::authorize('read', $name);
                return DB::getJob(JwtAuthentication::tenantId(), $this->getWorkerForJob($name), $args['id']);
            }
            if (str_starts_with($info->fieldName, 'import') && str_ends_with($info->fieldName, 'Preview')) {
                $name = substr($info->fieldName, 6, -8);
                Authorization::authorize('import', $name);
                return $this->resolveImportPreview($name, $args);
            }
        }
        throw new RuntimeException('no resolver found for '. $info->returnType->toString());
    }

    /**
     * @param string $name
     * @param mixed[] $args
     * @return stdClass
     * @throws Error
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    protected function resolveImportPreview(string $name, array $args): stdClass
    {
        $total = 20;
        [$create, $update, $errors] = File::read($name, $args);
        $data = [];
        $max = $total - min((int)round($total/2), count($update));
        $i = 1;
        $ids = [];
        foreach ($update as $nr => $row) {
            $ids[$nr] = $row['id'];
        }
        $this->checkExistence($ids, $name, $errors);
        $model = model($name);
        foreach ($create as $row) {
            $data[] = $this->injectFakeValuesForImportPreview((object) $row, $model);
            if ($i >= $max) {
                break;
            }
            $i++;
        }
        foreach ($update as $row) {
            $data[] = $this->injectFakeValuesForImportPreview((object) $row, $model);
            if ($i >= $total) {
                break;
            }
            $i++;
        }
        return (object)[
            'data' => $data,
            'errors' => $errors
        ];
    }

    protected function injectFakeValuesForImportPreview(stdClass $row, Model $model): stdClass
    {
        foreach ($model as $key => $field) {
            if ($field instanceof Field && !$field->null) {
                $row->$key = match ($field->type) {
                    Type::ID => 'NEW',
                    Field::DELETED_AT, Field::UPDATED_AT, Field::CREATED_AT, Field::DATE_TIME, Field::DATE, Field::TIME => time() * 1000,
                    Field::AUTO_INCREMENT, Type::INT => 0,
                    Field::DECIMAL, Type::FLOAT => 0.0,
                    default => '',
                };
            }
        }
        return $row;
    }

    protected function checkExistence(array $ids, string $name, array &$errors): void
    {
        $model = model($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant(JwtAuthentication::tenantId());

        $query->select(['id'])
            ->where(['column' => 'id', 'operator' => 'IN', 'value' => $ids])
            ->withTrashed();

        $databaseIds = [];
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
            $databaseIds[] = $row->id;
        }
        $rowNumbers = array_flip($ids);
        foreach (array_diff($ids, $databaseIds) as $missingId) {
            $errors[] = [
                'row' => $rowNumbers[$missingId] ?? 0,
                'column' => FileImport2::$lastIdColumn,
                'value' => $missingId,
                'relation' => null,
                'field' => 'id',
                'ignored' => false,
                'message' => 'ID not found.'
            ];
        }
    }


    protected function getWorkerForJob(string $name): string
    {
        return match($name) {
            'ImportJob' => 'importer',
            'ExportJob' => 'exporter',
        };
    }

    protected function getDiagram(): string
    {
        $classes = [
            '```mermaid',
            'classDiagram',
        ];
        $relations = [];
        $t = '    ';
        foreach (ClassFinder::models() as $name => $classname) {
            $model = new $classname();
            $classes[] = $t . 'class ' . $name . '{';
            foreach ($model as $key => $item) {
                if ($item instanceof Field) {
                    if (
                        $item->type === Field::CREATED_AT
                        || $item->type === Field::UPDATED_AT
                        || $item->type === Field::DELETED_AT
                        || $item->type === Type::ID
                    ) {
                        continue;
                    }
                    $classes[] = $t . $t . strtoupper($item->type) . ' ' . $key . ($item->null?'':'!');
                } elseif ($item instanceof Relation) {
                    $classes[] = $t . $t . $item->type . '(' . $item->name . ') ' . $key ;
                    if ($item->type === Relation::BELONGS_TO || $item->type === Relation::BELONGS_TO_MANY) {
                        $pivotFields = [];
                        foreach ($item as $subKey => $subItem) {
                            if ($subItem instanceof Field) {
                                if (
                                    $subItem->type === Field::CREATED_AT
                                    || $subItem->type === Field::UPDATED_AT
                                    || $subItem->type === Field::DELETED_AT
                                    || $subItem->type === Type::ID
                                ) {
                                    continue;
                                }
                                $pivotFields[] = $subItem->type . ' ' . $subKey . ($subItem->null?'':'!');
                            }
                        }
                        $relations[] = $t . $name . ' --|> ' . $item->name . ' : ' . implode(PHP_EOL, $pivotFields);
                    }
                }
            }
            $classes[] = $t . '}';
        }
        $newline = 'NEWLINE';
        return implode($newline, $classes) . $newline . implode($newline, $relations);
    }

    protected function job(string $name): array
    {
        return [
            'type' => Type::get('_'. $name .'Job'),
            'description' => 'Get a single ' . $name . ' job by id.',
            'args' => [
                'id' => Type::nonNull(Type::id()),
                '_timezone' => Type::get('_TimezoneOffset'),
            ]
        ];
    }

    protected function jobs(string $name): array
    {
        $args = [
            'first' => Type::int(),
            'page' => Type::int(),
            'where' => Type::get('_Job_WhereConditions'),
        ];
        $args['orderBy'] = Type::listOf(Type::nonNull(Type::get('_Job_OrderByClause')));
        $args['_timezone'] = Type::get('_TimezoneOffset');

        return [
            'type' => Type::get('_' . $name . '_JobPaginator'),
            'description' => 'Get a paginated list of ' . $name . ' jobs filtered by given where clauses.',
            'args' => $args
        ];
    }

    protected function history(): array
    {
        $args = [
            'first' => Type::int(),
            'page' => Type::int(),
            'where' => Type::get('_History_WhereConditions'),
        ];
        $args['orderBy'] = Type::listOf(Type::nonNull(Type::get('_History_OrderByClause')));
        $args['_timezone'] = Type::get('_TimezoneOffset');

        return [
            'type' => Type::get('_History_Paginator'),
            'description' => 'Get a paginated list of history logs filtered by given where clauses.',
            'args' => $args
        ];
    }


}
