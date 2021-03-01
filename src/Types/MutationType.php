<?php


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
use stdClass;

class MutationType extends ObjectType
{

    public function __construct(TypeLoader $typeLoader)
    {
        $fields = [];
        foreach (ModelFinder::all() as $name) {
            $type = $typeLoader->load($name)();
            $classname = 'App\\Models\\' . $name;
            $model = new $classname();
            $fields['create' . $type->name] = $this->create($type, $model, $typeLoader);
            $fields['update' . $type->name] = $this->update($type, $model, $typeLoader);
            $fields['delete' . $type->name] = $this->delete($type);
            $fields['import' . $type->name . 's'] = $this->import($type, $typeLoader);
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

    protected function create(ModelType $type, Model $model, TypeLoader $typeLoader): array
    {
        $args = [];
        /**
         * @var string $name
         * @var Field $field
         */
        foreach ($model as $name => $field) {
            if ($field instanceof Relation) {
                $relation = $field;
                if ($relation->type === Relation::BELONGS_TO) {
                    $args[$name] = new NonNull($typeLoader->load('_' . $type->name . '_' . $name . 'Relation'));
                }
                if ($relation->type === Relation::BELONGS_TO_MANY) {
                    $args[$name.'s'] = new ListOfType(new NonNull($typeLoader->load('_' . $type->name . '_' . $name . 'Relation')));
                }
            }

            if (!$field instanceof Field) {
                continue;
            }
            if ($field->readonly === false) {
                if ($field->null === true || ($field->default ?? null) !== null) {
                    $args[$name] = $typeLoader->loadForField($field, $name);
                } else {
                    $args[$name] = new NonNull($typeLoader->loadForField($field, $name));
                }
            }
        }
        $ret = [
            'type' => $type,
            'description' => 'Create a single new ' .  $type->name . ' entry',
        ];
        if (count($args) > 0) {
            ksort($args);
            $ret['args'] = $args;
        }
        return $ret;
    }

    protected function update(ModelType $type, Model $model, TypeLoader $typeLoader): array
    {
        $args = [
            'id' => new nonNull(Type::id())
        ];
        /**
         * @var string $name
         * @var Field $field
         */
        foreach ($model as $name => $field) {
            if ($field instanceof Relation) {
                $relation = $field;
                if ($relation->type === Relation::BELONGS_TO) {
                    $args[$name] = $typeLoader->load('_' . $type->name . '_' . $name . 'Relation');
                }
                if ($relation->type === Relation::BELONGS_TO_MANY) {
                    $args[$name] = new ListOfType(new NonNull($typeLoader->load('_' . $type->name . '_' . $name . 'Relation')));
                }
            }

            if (!$field instanceof Field) {
                continue;
            }
            if ($field->readonly === false) {
                $args[$name] = $typeLoader->loadForField($field, $name);
            }
        }
        $ret = [
            'type' => $type,
            'description' => 'Modify an existing ' .  $type->name . ' entry',
        ];
        if (count($args) > 0) {
            ksort($args);
            $ret['args'] = $args;
        }
        return $ret;
    }

    protected function delete($type): array
    {
        return [
            'type' => $type,
            'description' => 'Delete a ' .  $type->name . ' entry by ID',
            'args' => [
                'id' => new NonNull(Type::id())
            ]
        ];
    }

    protected function import(ModelType $type, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_ImportSummary', $type),
            'description' => 'Import a list of ' .  $type->name . 's from a spreadsheet. If ID\'s are present, ' .  $type->name . 's will be updated - otherwise new ' .  $type->name . 's will be created. To completely replace the existing data set, delete everything before importing.' ,
            'args' => [
                'data_base64' => new NonNull(Type::string()),
                'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load('_' . $type->name . 'ExportColumn'))))
            ]
        ];
    }

    protected function resolve(array $rootValue, array $args, $context, ResolveInfo $info): ?stdClass
    {
        if (str_starts_with($info->fieldName, 'create')) {
            return DB::insert($info->returnType, $args);
        }
        if (str_starts_with($info->fieldName, 'update')) {
            return DB::update($info->returnType, $args);
        }
        if (str_starts_with($info->fieldName, 'delete')) {
            return DB::delete($info->returnType, $args['id']);
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