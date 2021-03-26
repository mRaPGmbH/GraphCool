<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\Scalars\TimezoneOffset;
use Mrap\GraphCool\Utils\FileExport;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\ModelFinder;
use Mrap\GraphCool\Utils\TimeZone;
use stdClass;

class QueryType extends ObjectType
{

    public function __construct(TypeLoader $typeLoader)
    {
        $fields = [];
        foreach (ModelFinder::all() as $name) {
            $classname = 'App\\Models\\' . $name;
            $model = new $classname();

            $fields[lcfirst($name)] = $this->read($name, $typeLoader);
            $fields[lcfirst($name) . 's'] = $this->list($name, $typeLoader);
            $fields['export' . $name . 's'] = $this->export($name, $model, $typeLoader);
//            $fields['previewImport' . $type->name . 's'] = $this->previewImport($type, $typeLoader);
        }
        ksort($fields);
        $config = [
            'name'   => 'Query',
            'fields' => $fields,
            'resolveField' => function($rootValue, $args, $context, $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    protected function read(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $type = $typeLoader->load($name),
            'description' => 'Get a single ' .  $name . ' by it\'s ID',
            'args' => [
                'id' => new NonNull(Type::id()),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }

    protected function list(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_' . $name.'Paginator'),
            'description' => 'Get a paginated list of ' .  $name . 's filtered by given where clauses.',
            'args' => [
                'first'=> Type::int(),
                'page' => Type::int(),
                'where' => $typeLoader->load('_' . $name . 'WhereConditions'),
                'orderBy' => new ListOfType(new NonNull($typeLoader->load('_' . $name . 'OrderByClause'))),
                'search' => Type::string(),
                'result' => $typeLoader->load('_Result'),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }

    protected function export(string $name, Model $model, TypeLoader $typeLoader): array
    {
        $args = [
            'type' => new NonNull($typeLoader->load('_ExportFile')),
            'where' => $typeLoader->load('_' . $name . 'WhereConditions'),
            'orderBy' => new ListOfType(new NonNull($typeLoader->load('_' . $name . 'OrderByClause'))),
            'search' => Type::string(),
            'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load('_' . $name . 'ExportColumn')))),
            '_timezone' => $typeLoader->load('_TimezoneOffset'),
        ];

        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::HAS_ONE) {
                $args[$key] = new ListOfType(new NonNull($typeLoader->load('_' . $name . '__' . $key . 'EdgeColumn')));
            }
            if ($relation->type === Relation::BELONGS_TO_MANY) {
                $args[$key] = new ListOfType(new NonNull($typeLoader->load('_' . $name . '__' . $key . 'EdgeSelector')));
            }
        }
        $args['result'] = $typeLoader->load('_Result');
        $args['_timezone'] = $typeLoader->load('_TimezoneOffset');

        return [
            'type' => $typeLoader->load('_FileExport'),
            'description' => 'Export ' .  $name . 's filtered by given where clauses as a spreadsheet file (XLSX, CSV or ODS).',
            'args' => $args,
        ];
    }

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
    }

    protected function resolve(array $rootValue, array $args, $context, ResolveInfo $info): ?stdClass
    {
        JwtAuthentication::authenticate();

        if (isset($args['_timezone'])) {
            TimeZone::set($args['_timezone']);
        }

        if (is_object($info->returnType)) {
            if (strpos($info->returnType->name, 'Paginator') > 0) {
                return DB::findAll(substr($info->returnType->toString(), 1,-9), $args);
            }

            $type = $args['type'] ?? null;
            if ($info->returnType->name === '_FileExport') {
                $name = ucfirst(substr($info->fieldName, 6, -1));
                $exporter = new FileExport();
                return $exporter->export($name, DB::findAll($name, $args)->data ?? [], $args, $type);
            }
        }
        return DB::load($info->returnType->toString(), $args['id']);
    }

}