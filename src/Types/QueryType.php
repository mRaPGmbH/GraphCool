<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\TimeZone;

class QueryType extends ObjectType
{

    /** @var callable[] */
    protected array $customResolvers = [];

    public function __construct(TypeLoader $typeLoader)
    {
        $fields = [];
        foreach (ClassFinder::models() as $name => $classname) {
            $model = new $classname();
            $fields[lcfirst($name)] = $this->read($name, $typeLoader);
            $fields[lcfirst($name) . 's'] = $this->list($name, $model, $typeLoader);
            $fields['export' . $name . 's'] = $this->export($name, $model, $typeLoader);
//            $fields['previewImport' . $type->name . 's'] = $this->previewImport($type, $typeLoader);
        }
        foreach (ClassFinder::queries() as $name => $classname) {
            $query = new $classname($typeLoader);
            $fields[$query->name] = $query->config;
            $this->customResolvers[$query->name] = static function ($rootValue, $args, $context, $info) use ($query) {
                $query->authenticate();
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

        return [
            'type' => $typeLoader->load('_FileExport'),
            'description' => 'Export ' . $name . 's filtered by given where clauses as a spreadsheet file (XLSX, CSV or ODS).',
            'args' => $args,
        ];
    }

    /*
    protected function previewImport(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_' . $name.'Paginator'),
            'description' => 'Get a preview of what an import of a list of ' .  $name . 's from a spreadsheet would result in. Does not actually modify any data.' ,
            'args' => [
                'data_base64' => new NonNull(Type::string()),
                'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load('_' . $name . 'ExportColumn')))),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }*/

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
        JwtAuthentication::authenticate();

        if (is_object($info->returnType)) {
            if (strpos($info->returnType->name, 'Paginator') > 0) {
                return DB::findAll(JwtAuthentication::tenantId(), substr($info->returnType->toString(), 1, -9), $args);
            }

            $type = $args['type'] ?? 'xlsx';
            $args['first'] = 1048575; // max number of rows allowed in excel - 1 (for headers)
            if ($info->returnType->name === '_FileExport') {
                $name = ucfirst(substr($info->fieldName, 6, -1));
                return File::write(
                    $name,
                    DB::findAll(JwtAuthentication::tenantId(), $name, $args)->data ?? [],
                    $args,
                    $type
                );
            }
        }
        return DB::load(JwtAuthentication::tenantId(), $info->returnType->toString(), $args['id']);
    }

}