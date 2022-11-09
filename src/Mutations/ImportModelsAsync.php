<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Mutations;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;
use RuntimeException;
use function Mrap\GraphCool\pluralize;

class ImportModelsAsync extends Mutation
{
    use ModelBased;

    public function __construct(?string $model = null)
    {
        if ($model === null) {
            throw new RuntimeException(__METHOD__.': parameter $model may not be null for ModelBased mutations.');
        }
        $plural = pluralize($model);
        $this->name = 'import'.$plural.'Async';
        $this->model = $model;

        $this->config = [
            'type' => Type::string(),
            'description' => 'Import a list of ' . $plural . ' from a spreadsheet - in the background. Will return the job_id of the background job.',
            'args' => $this->importArgs($model)
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('import', $this->model);
        if (!isset($args['data_base64']) || empty($args['data_base64'])) {
            if (($args['file']['tmp_name'] ?? null) === null) {
                throw new Error('Neither data_base64 nor file received.');
            }
            $data = file_get_contents($args['file']['tmp_name']);
            $args['data_base64'] = base64_encode($data);
            unset($args['file']);
        }
        $data = [
            'name' => $this->model,
            'args' => $args,
        ];
        return DB::addJob(JwtAuthentication::tenantId(), 'importer', $this->model, $data);
    }
}
