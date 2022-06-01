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
use Mrap\GraphCool\DataSource\FullTextIndex;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\TimeZone;
use RuntimeException;
use stdClass;

class MutationType extends ObjectType
{

    /** @var callable[] */
    protected array $customResolvers = [];

    public function __construct(TypeLoader $typeLoader)
    {
        foreach (ClassFinder::models() as $name => $classname) {
            $model = new $classname();
            $fields['create' . $name] = $this->create($name, $model, $typeLoader);
            $fields['update' . $name] = $this->update($name, $typeLoader);
            $fields['updateMany' . $name . 's'] = $this->updateMany($name, $typeLoader);
            $fields['delete' . $name] = $this->delete($name, $typeLoader);
            $fields['restore' . $name] = $this->restore($name, $typeLoader);
            $fields['import' . $name . 's'] = $this->import($name, $typeLoader);
            $fields['import' . $name . 'sAsync'] = $this->importAsync($name, $typeLoader);
        }
        foreach (ClassFinder::mutations() as $name => $classname) {
            /** @var Mutation $query */
            $query = new $classname($typeLoader);
            $fields[$query->name] = $query->config;
            $this->customResolvers[$query->name] = static function ($rootValue, $args, $context, $info) use ($query) {
                return $query->resolve($rootValue, $args, $context, $info);
            };
        }

        ksort($fields);
        $config = [
            'name' => 'Mutation',
            'fields' => $fields,
            'resolveField' => function (array $rootValue, array $args, $context, ResolveInfo $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    /**
     * @param string $name
     * @param Model $model
     * @param TypeLoader $typeLoader
     * @return mixed[]
     */
    protected function create(string $name, Model $model, TypeLoader $typeLoader): array
    {
        $args = [];
        foreach (get_object_vars($model) as $key => $field) {
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
                    $args[$key] = new ListOfType(
                        new NonNull($typeLoader->load('_' . $name . '__' . $key . 'ManyRelation'))
                    );
                }
            }

            if (!$field instanceof Field) {
                continue;
            }
            if ($field->readonly === false) {
                if ($field->null === true || ($field->default ?? null) !== null) {
                    $args[$key] = $typeLoader->loadForField($field, $name . '__' . $key, true);
                } else {
                    $args[$key] = new NonNull($typeLoader->loadForField($field, $name . '__' . $key, true));
                }
            }
        }
        $args['_timezone'] = $typeLoader->load('_TimezoneOffset');

        $ret = [
            'type' => $typeLoader->load($name),
            'description' => 'Create a single new ' . $name . ' entry',
        ];
        //if (count($args) > 0) {
            ksort($args);
            $ret['args'] = $args;
        //}
        return $ret;
    }

    /**
     * @param string $name
     * @param TypeLoader $typeLoader
     * @return mixed[]
     */
    protected function update(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load($name),
            'description' => 'Modify an existing ' . $name . ' entry',
            'args' => [
                'id' => new nonNull(Type::id()),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
                'data' => new NonNull($typeLoader->load('_' . $name . 'Input')),
            ]
        ];
    }

    /**
     * @param string $name
     * @param TypeLoader $typeLoader
     * @return mixed[]
     */
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

    /**
     * @param string $name
     * @param TypeLoader $typeLoader
     * @return mixed[]
     */
    protected function delete(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load($name),
            'description' => 'Delete a ' . $name . ' entry by ID',
            'args' => [
                'id' => new NonNull(Type::id()),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }

    /**
     * @param string $name
     * @param TypeLoader $typeLoader
     * @return mixed[]
     */
    protected function restore(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load($name),
            'description' => 'Restore a previously soft-deleted ' . $name . ' record by ID',
            'args' => [
                'id' => new NonNull(Type::id()),
                '_timezone' => $typeLoader->load('_TimezoneOffset'),
            ]
        ];
    }

    /**
     * @param string $name
     * @param TypeLoader $typeLoader
     * @return mixed[]
     */
    protected function import(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_ImportSummary'),
            'description' => 'Import a list of ' . $name . 's from a spreadsheet. If ID\'s are present, ' . $name . 's will be updated - otherwise new ' . $name . 's will be created. To completely replace the existing data set, delete everything before importing.',
            'args' => $this->importArgs($name, $typeLoader)
        ];
    }

    protected function importAsync(string $name, TypeLoader $typeLoader): array
    {
        return [
            'type' => Type::string(),
            'description' => 'Import a list of ' . $name . 's from a spreadsheet - in the background. Will return the job_id of the background job.',
            'args' => $this->importArgs($name, $typeLoader)
        ];
    }

    protected function importArgs(string $name, TypeLoader $typeLoader): array
    {
        $args = [
            'file' => $typeLoader->load('_Upload'),
            'data_base64' => Type::string(),
            'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load('_' . $name . 'ColumnMapping')))),
            '_timezone' => $typeLoader->load('_TimezoneOffset'),
        ];
        $model = Model::get($name);
        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type === Relation::BELONGS_TO_MANY) {
                $args[$key] = new ListOfType(
                    new NonNull($typeLoader->load('_' . $name . '__' . $key . 'EdgeReducedSelector'))
                );
            }
        }
        return $args;
    }

    /**
     * @param mixed[] $rootValue
     * @param mixed[] $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     * @throws \GraphQL\Error\Error
     */
    protected function resolve(array $rootValue, array $args, $context, ResolveInfo $info): mixed
    {
        if (isset($args['_timezone'])) {
            TimeZone::set($args['_timezone']);
        }

        if (isset($this->customResolvers[$info->fieldName])) {
            return $this->customResolvers[$info->fieldName]($rootValue, $args, $context, $info);
        }

        if (str_starts_with($info->fieldName, 'create')) {
            $name = $info->returnType->toString();
            Authorization::authorize('create', $name);
            return DB::insert(JwtAuthentication::tenantId(), $name, $args);
        }
        if (str_starts_with($info->fieldName, 'updateMany')) {
            $name = substr($info->fieldName, 10, -1);
            Authorization::authorize('updateMany', $name);
            return DB::updateAll(JwtAuthentication::tenantId(), $name, $args);
        }
        if (str_starts_with($info->fieldName, 'update')) {
            $name = $info->returnType->toString();
            Authorization::authorize('update', $name);
            return DB::update(JwtAuthentication::tenantId(), $name, $args);
        }
        if (str_starts_with($info->fieldName, 'delete')) {
            $name = $info->returnType->toString();
            Authorization::authorize('delete', $name);
            return DB::delete(JwtAuthentication::tenantId(), $info->returnType->toString(), $args['id']);
        }
        if (str_starts_with($info->fieldName, 'restore')) {
            $name = $info->returnType->toString();
            Authorization::authorize('restore', $name);
            return DB::restore(JwtAuthentication::tenantId(), $info->returnType->toString(), $args['id']);
        }
        if (str_starts_with($info->fieldName, 'import')) {
            if (str_ends_with($info->fieldName, 'Async')) {
                $name = substr($info->fieldName, 6, -6);
                Authorization::authorize('import', $name);
                return $this->resolveImportAsync($name, $args);
            } else {
                $name = substr($info->fieldName, 6, -1);
                Authorization::authorize('import', $name);
                return $this->resolveImport($name, $args);
            }
        }
        throw new RuntimeException(print_r($info->fieldName, true));
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
        [$create, $update, $errors] = File::read($name, $args);
        $inserted_ids = [];
        foreach ($create as $data) {
            $inserted_ids[] = DB::insert(JwtAuthentication::tenantId(), $name, $data)->id;
        }
        $updated_ids = [];
        foreach ($update as $data) {
            $updated_ids[] = DB::update(JwtAuthentication::tenantId(), $name, $data)->id;
        }
        $affected_ids = array_merge($inserted_ids, $updated_ids);
        foreach ($affected_ids as $id) {
            FullTextIndex::index(JwtAuthentication::tenantId(), $name, $id);
        }
        return (object)[
            'inserted_rows' => count($inserted_ids),
            'inserted_ids' => $inserted_ids,
            'updated_rows' => count($updated_ids),
            'updated_ids' => $updated_ids,
            'affected_rows' => count($affected_ids),
            'affected_ids' => $affected_ids,
            'failed_rows' => 0,
            'failed_row_numbers' => [],
            'errors' => $errors
        ];
    }

    /**
     * @param string $name
     * @param mixed[] $args
     * @return string
     */
    protected function resolveImportAsync(string $name, array $args): string
    {
        if (!isset($args['data_base64']) || empty($args['data_base64'])) {
            if (($args['file']['tmp_name'] ?? null) === null) {
                throw new Error('Neither data_base64 nor file received.');
            }
            $data = file_get_contents($args['file']['tmp_name']);
            $args['data_base64'] = base64_encode($data);
            unset($args['file']);
        }
        $data = [
            'name' => $name,
            'args' => $args,
        ];
        return DB::addJob(JwtAuthentication::tenantId(), 'importer', $data);
    }

}
