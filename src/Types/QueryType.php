<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlQueryBuilder;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\FileImport2;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\TimeZone;
use stdClass;

class QueryType extends ObjectType
{

    /** @var callable[] */
    protected array $customResolvers = [];

    public function __construct(TypeLoader $typeLoader)
    {
        $fields = [
            '_classDiagram' => Type::string(),
            '_ImportJob' => $this->job('Import', $typeLoader),
            '_ImportJobs' => $this->jobs('Import', $typeLoader),
            '_ExportJob' => $this->job('Export', $typeLoader),
            '_ExportJobs' => $this->jobs('Export', $typeLoader),
            '_History' => $this->history($typeLoader),
            '_Token' => $this->token($typeLoader),
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
            $fields[lcfirst($name)] = $this->read($name, $typeLoader);
            $fields[lcfirst($name) . 's'] = $this->list($name, $model, $typeLoader);
            $fields['export' . $name . 's'] = $this->export($name, $model, $typeLoader);
            $fields['export' . $name . 'sAsync'] = $this->exportAsync($name, $model, $typeLoader);
            $fields['import' . $name . 'sPreview'] = $this->previewImport($name, $typeLoader);
        }
        foreach (ClassFinder::queries() as $name => $classname) {
            $query = new $classname($typeLoader);
            $fields[$query->name] = $query->config;
            $this->customResolvers[$query->name] = static function ($rootValue, $args, $context, $info) use ($query) {
                return $query->resolve($rootValue, $args, $context, $info);
            };
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

    protected function token(TypeLoader $typeLoader): array
    {
        return [
            'type' => Type::nonNull(Type::string()),
            'description' => 'Get a single use JWT for a specific endpoint of this service.',
            'args' => [
                'endpoint' => $typeLoader->load('_Entity'),
                'operation' => $typeLoader->load('_Permission'),
            ]
        ];
    }

    /**
     * @param string $name
     * @param TypeLoader $typeLoader
     * @return mixed[]
     */
    protected function read(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load($name),
            'description' => 'Get a single ' . $name . ' by it\'s ID',
            'args' => [
                'id' => new NonNull(Type::id()),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }

    /**
     * @param string $name
     * @param Model $model
     * @param TypeLoader $typeLoader
     * @return mixed[]
     */
    protected function list(string $name, Model $model, TypeLoader $typeLoader): array
    {
        $args = [
            'first' => Type::int(),
            'page' => Type::int(),
            'where' => $typeLoader->load('_' . $name . 'WhereConditions'),
            'whereMode' => $typeLoader->load('_WhereMode'),
        ];
        foreach (get_object_vars($model) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $args['where' . ucfirst($key)] = $typeLoader->load('_' . $relation->name . 'WhereConditions');
        }
        $args['orderBy'] = new ListOfType(new NonNull($typeLoader->load('_' . $name . 'OrderByClause')));
        $args['search'] = Type::string();
        $args['searchLoosely'] = Type::string();
        $args['result'] = $typeLoader->load('_Result');
        $args['_timezone'] = $typeLoader->load('_TimezoneOffset');

        return [
            'type' => $typeLoader->load('_' . $name . 'Paginator'),
            'description' => 'Get a paginated list of ' . $name . 's filtered by given where clauses.',
            'args' => $args
        ];
    }

    /**
     * @param string $name
     * @param Model $model
     * @param TypeLoader $typeLoader
     * @return mixed[]
     */
    protected function export(string $name, Model $model, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_FileExport'),
            'description' => 'Export ' . $name . 's filtered by given where clauses as a spreadsheet file (XLSX, CSV or ODS).',
            'args' => $this->exportArgs($name, $model, $typeLoader),
        ];
    }

    protected function exportAsync(string $name, Model $model, TypeLoader $typeLoader): array
    {
        return[
            'type' => Type::string(),
            'description' => 'Start background export of ' . $name . 's and get the ID of the _ExportJob you can later fetch the file from.',
            'args' => $this->exportArgs($name, $model, $typeLoader),
        ];
    }

    protected function exportArgs(string $name, Model $model, TypeLoader $typeLoader): array
    {
        $args = [
            'type' => new NonNull($typeLoader->load('_ExportFile')),
            'where' => $typeLoader->load('_' . $name . 'WhereConditions'),
            'orderBy' => new ListOfType(new NonNull($typeLoader->load('_' . $name . 'OrderByClause'))),
            'search' => Type::string(),
            'searchLoosely' => Type::string(),
            'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load('_' . $name . 'ColumnMapping')))),
        ];

        foreach (get_object_vars($model) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::HAS_ONE) {
                $args[$key] = new ListOfType(
                    new NonNull($typeLoader->load('_' . $name . '__' . $key . 'EdgeColumnMapping'))
                );
            }
            if ($relation->type === Relation::BELONGS_TO_MANY) {
                $args[$key] = new ListOfType(
                    new NonNull($typeLoader->load('_' . $name . '__' . $key . 'EdgeSelector'))
                );
            }
            $args['where' . ucfirst($key)] = $typeLoader->load('_' . $relation->name . 'WhereConditions');
        }
        $args['result'] = $typeLoader->load('_Result');
        $args['_timezone'] = $typeLoader->load('_TimezoneOffset');
        return $args;
    }

    protected function previewImport(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_' . $name.'ImportPreview'),
            'description' => 'Get a preview of what an import of a list of ' .  $name . 's from a spreadsheet would result in. Does not actually modify any data.' ,
            'args' => [
                'file' => $typeLoader->load('_Upload'),
                'data_base64' => Type::string(),
                'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load('_' . $name . 'ColumnMapping')))),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
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
                return DB::addJob(JwtAuthentication::tenantId(), 'exporter', $data);
            }
            if ($info->returnType->name === '_FileExport') {
                $name = ucfirst(substr($info->fieldName, 6, -1));
                Authorization::authorize('export', $name);
                return File::write(
                    $name,
                    DB::findAll(JwtAuthentication::tenantId(), $name, $args)->data ?? [],
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
                return $this->resolveImport($name, $args);
            }
        }
        $name = $info->returnType->toString();
        Authorization::authorize('read', $name);
        return DB::load(JwtAuthentication::tenantId(), $name, $args['id']);
    }

    /**
     * @param string $name
     * @param mixed[] $args
     * @return stdClass
     * @throws Error
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    protected function resolveImport(string $name, array $args): stdClass
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
        foreach ($create as $row) {
            $row['id'] = 'NEW';
            $row['created_at'] = time() * 1000;
            $data[] = (object) $row;
            if ($i >= $max) {
                break;
            }
            $i++;
        }
        foreach ($update as $row) {
            $row['created_at'] = time() * 1000;
            $data[] = (object) $row;
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

    protected function checkExistence(array $ids, string $name, array &$errors): void
    {
        $model = Model::get($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant(JwtAuthentication::tenantId());

        $query->select(['id'])
            ->where(['column' => 'id', 'operator' => 'IN', 'value' => $ids])
            ->withTrashed();

        $databaseIds = [];
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
            $databaseIds[] = $row['id'];
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

    protected function job(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_'. $name .'Job'),
            'description' => 'Get a single ' . $name . ' job by id.',
            'args' => [
                'id' => new NonNull(Type::id()),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }

    protected function jobs(string $name, TypeLoader $typeLoader): array
    {
        $args = [
            'first' => Type::int(),
            'page' => Type::int(),
            'where' => $typeLoader->load('_Job_WhereConditions'),
        ];
        $args['orderBy'] = new ListOfType(new NonNull($typeLoader->load('_Job_OrderByClause')));
        $args['_timezone'] = $typeLoader->load('_TimezoneOffset');

        return [
            'type' => $typeLoader->load('_' . $name . '_JobPaginator'),
            'description' => 'Get a paginated list of ' . $name . ' jobs filtered by given where clauses.',
            'args' => $args
        ];
    }

    protected function history(TypeLoader $typeLoader): array
    {
        $args = [
            'first' => Type::int(),
            'page' => Type::int(),
            'where' => $typeLoader->load('_History_WhereConditions'),
        ];
        $args['orderBy'] = new ListOfType(new NonNull($typeLoader->load('_History_OrderByClause')));
        $args['_timezone'] = $typeLoader->load('_TimezoneOffset');

        return [
            'type' => $typeLoader->load('_History_Paginator'),
            'description' => 'Get a paginated list of history logs filtered by given where clauses.',
            'args' => $args
        ];
    }


}
