<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Utils\FileImport;
use Mrap\GraphCool\Utils\ModelFinder;
use Mrap\GraphCool\Utils\TimeZone;
use stdClass;

class MutationType extends ObjectType
{

    public function __construct(TypeLoader $typeLoader)
    {
        $fields = [];
        foreach (ModelFinder::all() as $name) {
            $classname = 'App\\Models\\' . $name;
            $model = new $classname();
            $fields['create' . $name] = $this->create($name, $model, $typeLoader);
            $fields['update' . $name] = $this->update($name, $model, $typeLoader);
            $fields['delete' . $name] = $this->delete($name, $typeLoader);
            $fields['restore' . $name] = $this->restore($name, $typeLoader);
            $fields['import' . $name . 's'] = $this->import($name, $typeLoader);
        }
        ksort($fields);
        $config = [
            'name'   => 'Mutation',
            'fields' => $fields,
            'resolveField' => function(array $rootValue, array $args, $context, ResolveInfo $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    protected function create(string $name, Model $model, TypeLoader $typeLoader): array
    {
        $args = [];
        /**
         * @var string $key
         * @var Field $field
         */
        foreach ($model as $key => $field) {
            if ($field instanceof Relation) {
                $relation = $field;
                if ($relation->type === Relation::BELONGS_TO) {
                    $args[$key] = new NonNull($typeLoader->load('_' . $name . '_' . $key . 'Relation'));
                }
                if ($relation->type === Relation::BELONGS_TO_MANY) {
                    $args[$key] = new ListOfType(new NonNull($typeLoader->load('_' . $name . '_' . $key . 'Relation')));
                }
            }

            if (!$field instanceof Field) {
                continue;
            }
            if ($field->readonly === false) {
                if ($field->null === true || ($field->default ?? null) !== null) {
                    $args[$key] = $typeLoader->loadForField($field, $key);
                } else {
                    $args[$key] = new NonNull($typeLoader->loadForField($field, $key));
                }
            }
        }
        $args['_timezone'] = $typeLoader->load('_TimezoneOffset');

        $ret = [
            'type' => $typeLoader->load($name),
            'description' => 'Create a single new ' .  $name . ' entry',
        ];
        if (count($args) > 0) {
            ksort($args);
            $ret['args'] = $args;
        }
        return $ret;
    }

    protected function update(string $name, Model $model, TypeLoader $typeLoader): array
    {
        $args = [
            'id' => new nonNull(Type::id())
        ];
        /**
         * @var string $key
         * @var Field $field
         */
        foreach ($model as $key => $field) {
            if ($field instanceof Relation) {
                $relation = $field;
                if ($relation->type === Relation::BELONGS_TO) {
                    $args[$key] = $typeLoader->load('_' . $name . '_' . $key . 'Relation');
                }
                if ($relation->type === Relation::BELONGS_TO_MANY) {
                    $args[$key] = new ListOfType(new NonNull($typeLoader->load('_' . $name . '_' . $key . 'Relation')));
                }
            }

            if (!$field instanceof Field) {
                continue;
            }
            if ($field->readonly === false) {
                $args[$key] = $typeLoader->loadForField($field, $key);
            }
        }
        $ret = [
            'type' => $typeLoader->load($name),
            'description' => 'Modify an existing ' .  $name . ' entry',
        ];
        $args['_timezone'] = $typeLoader->load('_TimezoneOffset');

        if (count($args) > 0) {
            ksort($args);
            $ret['args'] = $args;
        }
        return $ret;
    }

    protected function delete(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load($name),
            'description' => 'Delete a ' .  $name . ' entry by ID',
            'args' => [
                'id' => new NonNull(Type::id()),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }

    protected function restore(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load($name),
            'description' => 'Restore a previously soft-deleted ' .  $name . ' record by ID',
            'args' => [
                'id' => new NonNull(Type::id()),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }

    protected function import(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_ImportSummary'),
            'description' => 'Import a list of ' .  $name . 's from a spreadsheet. If ID\'s are present, ' .  $name . 's will be updated - otherwise new ' .  $name . 's will be created. To completely replace the existing data set, delete everything before importing.' ,
            'args' => [
                'data_base64' => new NonNull(Type::string()),
                'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load('_' . $name . 'ExportColumn')))),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }

    protected function resolve(array $rootValue, array $args, $context, ResolveInfo $info): ?stdClass
    {
        if (isset($args['_timezone'])) {
            TimeZone::set($args['_timezone']);
        }

        if (str_starts_with($info->fieldName, 'create')) {
            return DB::insert($info->returnType->toString(), $args);
        }
        if (str_starts_with($info->fieldName, 'update')) {
            return DB::update($info->returnType->toString(), $args);
        }
        if (str_starts_with($info->fieldName, 'delete')) {
            return DB::delete($info->returnType->toString(), $args['id']);
        }
        if (str_starts_with($info->fieldName, 'restore')) {
            return DB::restore($info->returnType->toString(), $args['id']);
        }
        if (str_starts_with($info->fieldName, 'import')) {
            $name = substr($info->fieldName, 6, -1);
            $importer = new FileImport();
            $result = new stdClass();
            $result->updated_rows = 0;
            $result->updated_ids = [];
            $result->inserted_rows = 0;
            $result->inserted_ids = [];
            foreach ($importer->import($args['data_base64'], $args['columns']) as $item) {
                if (isset($item['id'])) {
                    $item = DB::update($name, $item);
                    $result->updated_rows += 1;
                    $result->updated_ids[] = $item->id;
                } else {
                    $item = DB::insert($name, $item);
                    $result->inserted_rows += 1;
                    $result->inserted_ids[] = $item->id;
                }
            }
            $result->affected_rows = $result->inserted_rows + $result->updated_rows;
            $result->affected_ids = array_merge($result->inserted_ids, $result->updated_ids);
            return $result;
        }
        throw new \Exception(print_r($info->fieldName, true));
    }


}