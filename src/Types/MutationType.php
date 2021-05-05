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
use Mrap\GraphCool\Utils\FileImport;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\TimeZone;
use stdClass;

class MutationType extends ObjectType
{

    protected array $customResolvers =  [];

    public function __construct(TypeLoader $typeLoader)
    {
        $fields = [];
        foreach (ClassFinder::models() as $name => $classname) {
            $model = new $classname();
            $fields['create' . $name] = $this->create($name, $model, $typeLoader);
            $fields['update' . $name] = $this->update($name, $typeLoader);
            $fields['updateMany' . $name . 's'] = $this->updateMany($name, $typeLoader);
            $fields['delete' . $name] = $this->delete($name, $typeLoader);
            $fields['restore' . $name] = $this->restore($name, $typeLoader);
            $fields['import' . $name . 's'] = $this->import($name, $typeLoader);
        }
        foreach (ClassFinder::mutations() as $name => $classname) {
            $query = new $classname($typeLoader);
            $fields[$query->name] = $query->config;
            $this->customResolvers[$query->name] = static function($rootValue, $args, $context, $info) use ($query) {
                return $query->resolve($rootValue, $args, $context, $info);
            };
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
                    if ($relation->null) {
                        $args[$key] = $typeLoader->load('_' . $name . '__' . $key . 'Relation');
                    } else {
                        $args[$key] = new NonNull($typeLoader->load('_' . $name . '__' . $key . 'Relation'));
                    }
                }
                if ($relation->type === Relation::BELONGS_TO_MANY) {
                    $args[$key] = new ListOfType(new NonNull($typeLoader->load('_' . $name . '__' . $key . 'ManyRelation')));
                }
            }

            if (!$field instanceof Field) {
                continue;
            }
            if ($field->readonly === false) {
                if ($field->null === true || ($field->default ?? null) !== null) {
                    $args[$key] = $typeLoader->loadForField($field, $name . '__' . $key);
                } else {
                    $args[$key] = new NonNull($typeLoader->loadForField($field, $name . '__' . $key));
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

    protected function update(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load($name),
            'description' => 'Modify an existing ' .  $name . ' entry',
            'args' => [
                'id' => new nonNull(Type::id()),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
                'data' => new NonNull($typeLoader->load('_' . $name . 'Input')),
            ]
        ];
    }

    protected function updateMany(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_UpdateManyResult'),
            'description' => 'Modify multiple existing ' . $name . ' entries, using where.',
            'args' => [
                'where' => $typeLoader->load('_' . $name . 'WhereConditions'),
                'data' => new NonNull($typeLoader->load('_' . $name . 'Input')),
            ]
        ];
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
                'file' => $typeLoader->load('_Upload'),
                'data_base64' => Type::string(),
                'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load('_' . $name . 'ExportColumn')))),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }

    protected function resolve(array $rootValue, array $args, $context, ResolveInfo $info)
    {
        JwtAuthentication::authenticate();

        if (isset($args['_timezone'])) {
            TimeZone::set($args['_timezone']);
        }

        if (isset($this->customResolvers[$info->fieldName])) {
            return $this->customResolvers[$info->fieldName]($rootValue, $args, $context, $info);
        }

        if (str_starts_with($info->fieldName, 'create')) {
            return DB::insert(JwtAuthentication::tenantId(), $info->returnType->toString(), $args);
        }
        if (str_starts_with($info->fieldName, 'updateMany')) {
            return DB::updateAll(JwtAuthentication::tenantId(), substr($info->fieldName, 10, -1), $args);
        }
        if (str_starts_with($info->fieldName, 'update')) {
            return DB::update(JwtAuthentication::tenantId(), $info->returnType->toString(), $args);
        }
        if (str_starts_with($info->fieldName, 'delete')) {
            return DB::delete(JwtAuthentication::tenantId(), $info->returnType->toString(), $args['id']);
        }
        if (str_starts_with($info->fieldName, 'restore')) {
            return DB::restore(JwtAuthentication::tenantId(), $info->returnType->toString(), $args['id']);
        }
        if (str_starts_with($info->fieldName, 'import')) {
            return DB::import(JwtAuthentication::tenantId(), substr($info->fieldName, 6, -1), $args);
        }
        throw new \RuntimeException(print_r($info->fieldName, true));
    }


}