<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Utils\FileExport;
use Mrap\GraphCool\Utils\ModelFinder;
use stdClass;

class QueryType extends ObjectType
{

    public function __construct(TypeLoader $typeLoader)
    {
        $fields = [];
        foreach (ModelFinder::all() as $name) {
            $type = $typeLoader->load($name)();
            $fields[lcfirst($type->name)] = $this->read($type);
            $fields[lcfirst($type->name) . 's'] = $this->list($type, $typeLoader);
            $fields['export' . $type->name . 's'] = $this->export($type, $typeLoader);
//            $fields['previewImport' . $type->name . 's'] = $this->previewImport($type, $typeLoader);
        }
        $config = [
            'name'   => 'Query',
            'fields' => $fields,
            'resolveField' => function($rootValue, $args, $context, $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    protected function read($type): array
    {
        return [
            'type' => $type,
            'description' => 'Get a single ' .  $type->name . ' by it\'s ID',
            'args' => [
                'id' => new NonNull(Type::id())
            ]
        ];
    }

    protected function list($type, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_' . $type->name.'Paginator', $type),
            'description' => 'Get a paginated list of ' .  $type->name . 's filtered by given where clauses.',
            'args' => [
                'first'=> Type::int(),
                'page' => Type::int(),
                'where' => $typeLoader->load('_' . $type->name . 'WhereConditions', $type),
                'orderBy' => $typeLoader->load('_' . $type->name . 'OrderByClause', $type),
                'search' => Type::string(),
            ]
        ];
    }

    protected function export($type, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_FileExport', $type),
            'description' => 'Export ' .  $type->name . 's filtered by given where clauses as a sheet file (XLSX, CSV or ODS).',
            'args' => [
                'type' => new NonNull($typeLoader->load('_SheetFileEnum')),
                'where' => $typeLoader->load('_' . $type->name . 'WhereConditions', $type),
                'orderBy' => $typeLoader->load('_' . $type->name . 'OrderByClause', $type),
                'search' => Type::string(),
                'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load('_' . $type->name . 'ExportColumn'))))
            ]
        ];
    }

    protected function previewImport(ModelType $type, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_' . $type->name.'Paginator', $type),
            'description' => 'Get a preview of what an import of a list of ' .  $type->name . 's from a spreadsheet would result in. Does not actually modify any data.' ,
            'args' => [
                'data_base64' => new NonNull(Type::string()),
                'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load('_' . $type->name . 'ExportColumn'))))
            ]
        ];
    }

    protected function resolve(array $rootValue, array $args, $context, ResolveInfo $info): ?stdClass
    {
        if (is_object($info->returnType)) {
            if (strpos($info->returnType->name, 'Paginator') > 0) {
                return DB::findAll(substr($info->returnType->toString(), 1,-9), $args);
            }

            $type = $args['type'] ?? null;
            if ($info->returnType->name === '_FileExport') {
                $name = ucfirst(substr($info->fieldName, 6, -1));
                $exporter = new FileExport();
                return $exporter->export($name . '-Export_'.date('Y-m-d_H-i-s').'.'.$type, DB::findAll($name, $args)->data ?? [], $args['columns'], $type);
            }
        }
        return DB::load($info->returnType->toString(), $args['id']);
    }

}