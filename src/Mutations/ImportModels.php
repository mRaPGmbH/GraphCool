<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\FullTextIndex;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;
use RuntimeException;
use function Mrap\GraphCool\pluralize;

class ImportModels extends Mutation
{
    use ModelBased;

    public function __construct(?string $model = null)
    {
        if ($model === null) {
            throw new RuntimeException(__METHOD__.': parameter $model may not be null for ModelBased mutations.');
        }
        $plural = pluralize($model);
        $this->name = 'import'.$plural;
        $this->model = $model;

        $this->config = [
            'type' => Type::importSummary(),
            'description' => 'Import a list of ' . $plural . ' from a spreadsheet. If ID\'s are present, ' . $plural . ' will be updated - otherwise new ' . $plural . ' will be created. To completely replace the existing data set, delete everything before importing.',
            'args' => $this->importArgs($model)
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('import', $this->model);
        [$create, $update, $errors] = File::read($this->model, $args);
        $inserted_ids = [];
        foreach ($create as $data) {
            $inserted_ids[] = DB::insert(JwtAuthentication::tenantId(), $this->model, $data)->id;
        }
        $updated_ids = [];
        foreach ($update as $data) {
            $updated_ids[] = DB::update(JwtAuthentication::tenantId(), $this->model, $data)->id;
        }
        $affected_ids = array_merge($inserted_ids, $updated_ids);
        foreach ($affected_ids as $id) {
            FullTextIndex::index(JwtAuthentication::tenantId(), $this->model, $id);
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
}
