<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\FullTextIndex;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\TimeZone;
use RuntimeException;
use stdClass;
use function Mrap\GraphCool\model;

class MutationType extends BaseType
{

    /** @var callable[] */
    protected array $customResolvers = [];

    protected array $mutations = [];

    public function __construct(TypeLoader $typeLoader)
    {
        foreach (ClassFinder::models() as $name => $classname) {
            $model = new $classname();
            $fields['update' . $name] = $this->update($name);
            $fields['updateMany' . $name . 's'] = $this->updateMany($name);
            $fields['delete' . $name] = $this->delete($name);
            $fields['restore' . $name] = $this->restore($name);
            $fields['import' . $name . 's'] = $this->import($name);
            $fields['import' . $name . 'sAsync'] = $this->importAsync($name);
            $fields['export' . $name . 'sAsync'] = $this->exportAsync($name, $model);
        }
        foreach (ClassFinder::mutations() as $name => $classname) {
            if (in_array(ModelBased::class, (new \ReflectionClass($classname))->getTraitNames())) {
                foreach (ClassFinder::models() as $model => $tmp) {
                    $mutation = new $classname($model);
                    $this->mutations[$mutation->name] = $mutation;
                }
            } else {
                $mutation = new $classname();
                $this->mutations[$mutation->name] = $mutation;
            }

        }
        foreach ($this->mutations as $name => $mutation) {
            $fields[$name] = $mutation->config;
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
     * @return mixed[]
     */
    protected function update(string $name): array
    {
        return [
            'type' => Type::get($name),
            'description' => 'Modify an existing ' . $name . ' entry',
            'args' => [
                'id' => Type::nonNull(Type::id()),
                '_timezone' => Type::get('_TimezoneOffset'),
                'data' => Type::nonNull(Type::get('_' . $name . 'Input')),
            ]
        ];
    }

    /**
     * @param string $name
     * @return mixed[]
     */
    protected function updateMany(string $name): array
    {
        return [
            'type' => Type::get('_UpdateManyResult'),
            'description' => 'Modify multiple existing ' . $name . ' entries, using where.',
            'args' => [
                'where' => Type::get('_' . $name . 'WhereConditions'),
                'data' => Type::nonNull(Type::get('_' . $name . 'Input')),
            ]
        ];
    }

    /**
     * @param string $name
     * @return mixed[]
     */
    protected function delete(string $name): array
    {
        return [
            'type' => Type::get($name),
            'description' => 'Delete a ' . $name . ' entry by ID',
            'args' => [
                'id' => Type::nonNull(Type::id()),
                '_timezone' => Type::get('_TimezoneOffset'),
            ]
        ];
    }

    /**
     * @param string $name
     * @return mixed[]
     */
    protected function restore(string $name): array
    {
        return [
            'type' => Type::get($name),
            'description' => 'Restore a previously soft-deleted ' . $name . ' record by ID',
            'args' => [
                'id' => Type::nonNull(Type::id()),
                '_timezone' => Type::get('_TimezoneOffset'),
            ]
        ];
    }

    /**
     * @param string $name
     * @param TypeLoader $typeLoader
     * @return mixed[]
     */
    protected function import(string $name): array
    {
        return [
            'type' => Type::get('_ImportSummary'),
            'description' => 'Import a list of ' . $name . 's from a spreadsheet. If ID\'s are present, ' . $name . 's will be updated - otherwise new ' . $name . 's will be created. To completely replace the existing data set, delete everything before importing.',
            'args' => $this->importArgs($name)
        ];
    }

    protected function importAsync(string $name): array
    {
        return [
            'type' => Type::string(),
            'description' => 'Import a list of ' . $name . 's from a spreadsheet - in the background. Will return the job_id of the background job.',
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
    protected function resolve(array $rootValue, array $args, $context, ResolveInfo $info): mixed
    {
        if (isset($args['_timezone'])) {
            TimeZone::set($args['_timezone']);
        }

        if (isset($this->mutations[$info->fieldName])) {
            return $this->mutations[$info->fieldName]->resolve($rootValue, $args, $context, $info);
        }

        if (isset($this->customResolvers[$info->fieldName])) {
            return $this->customResolvers[$info->fieldName]($rootValue, $args, $context, $info);
        }

        if (str_starts_with($info->fieldName, 'updateMany')) {
            $name = substr($info->fieldName, 10, -1);
            Authorization::authorize('updateMany', $name);
            return DB::updateAll(JwtAuthentication::tenantId(), $name, $args);
        }
        if (str_starts_with($info->fieldName, 'update')) {
            $name = $info->returnType->toString();
            Authorization::authorize('update', $name);
            $model = model($name);
            $args['data'] = $model->udpateDerivedFields(JwtAuthentication::tenantId(), $args['data'], $args['id']);
            $result = DB::update(JwtAuthentication::tenantId(), $name, $args);
            if ($result !== null) {
                $model->onSave($result, $args);
                $model->onChange($result, $args);
            }
            return $result;
        }
        if (str_starts_with($info->fieldName, 'delete')) {
            $name = $info->returnType->toString();
            Authorization::authorize('delete', $name);
            $result = DB::delete(JwtAuthentication::tenantId(), $info->returnType->toString(), $args['id']);
            if ($result !== null) {
                $model = model($name);
                $model->onDelete($result);
            }
            return $result;
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
            }
            $name = substr($info->fieldName, 6, -1);
            Authorization::authorize('import', $name);
            return $this->resolveImport($name, $args);
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
        return DB::addJob(JwtAuthentication::tenantId(), 'importer', $name, $data);
    }

    protected function exportAsync(string $name, Model $model): array
    {
        return[
            'type' => Type::string(),
            'description' => 'Start background export of ' . $name . 's and get the ID of the _ExportJob you can later fetch the file from.',
            'args' => $this->exportArgs($name, $model),
        ];
    }

}
