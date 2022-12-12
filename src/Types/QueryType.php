<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\TimeZone;
use RuntimeException;

class QueryType extends ObjectType
{

    /** @var Query[] */
    protected array $queries = [];

    public function __construct()
    {
        parent::__construct([
            'name' => 'Query',
            'fields' => fn() => $this->fieldConfig(),
            'resolveField' => function ($rootValue, $args, $context, $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ]);
    }

    protected function fieldConfig(): array
    {
        $fields = [
            '_ImportJob' => $this->job('Import'),
            '_ImportJobs' => $this->jobs('Import'),
            '_ExportJob' => $this->job('Export'),
            '_ExportJobs' => $this->jobs('Export'),
            '_History' => $this->history(),
        ];
        foreach (ClassFinder::queries() as $name => $classname) {
            if (in_array(ModelBased::class, (new \ReflectionClass($classname))->getTraitNames())) {
                foreach (ClassFinder::models() as $model => $tmp) {
                    $query = new $classname($model);
                    $this->queries[$query->name] = $query;
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
        return $fields;
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
            if (str_starts_with($info->fieldName, '_') && str_ends_with($info->fieldName, 'Job')) {
                $name = substr($info->fieldName, 1);
                Authorization::authorize('read', $name);
                return DB::getJob(JwtAuthentication::tenantId(), $this->getWorkerForJob($name), $args['id']);
            }
        }
        throw new RuntimeException('No resolver found for: '. $info->fieldName);
    }

    protected function getWorkerForJob(string $name): string
    {
        return match($name) {
            'ImportJob' => 'importer',
            'ExportJob' => 'exporter',
        };
    }

    protected function job(string $name): array
    {
        return [
            'type' => Type::get('_'. $name .'Job'),
            'description' => 'Get a single ' . $name . ' job by id.',
            'args' => [
                'id' => Type::nonNull(Type::id()),
                '_timezone' => Type::timezoneOffset(),
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
        $args['_timezone'] = Type::timezoneOffset();

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
        $args['_timezone'] = Type::timezoneOffset();

        return [
            'type' => Type::get('_History_Paginator'),
            'description' => 'Get a paginated list of history logs filtered by given where clauses.',
            'args' => $args
        ];
    }


}
