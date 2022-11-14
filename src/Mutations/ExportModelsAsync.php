<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;
use RuntimeException;
use function Mrap\GraphCool\pluralize;

class ExportModelsAsync extends Mutation
{
    use ModelBased;

    public function __construct(?string $model = null)
    {
        if ($model === null) {
            throw new RuntimeException(__METHOD__.': parameter $model may not be null for ModelBased mutations.');
        }
        $plural = pluralize($model);
        $this->name = 'export'.$plural.'Async';
        $this->model = $model;

        $this->config = [
            'type' => Type::string(),
            'description' => 'Start background export of ' . $plural . ' and get the ID of the _ExportJob you can later fetch the file from.',
            'args' => $this->exportArgs($model),
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('export', $this->model);
        $args['first'] = 1048575; // max number of rows allowed in excel - 1 (for headers)
        $data = [
            'name' => $this->model,
            'args' => $args,
            'jwt' => File::getToken(),
        ];
        return DB::addJob(JwtAuthentication::tenantId(), 'exporter', $this->model, $data);
    }
}
