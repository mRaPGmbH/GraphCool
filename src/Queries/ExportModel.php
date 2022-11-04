<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Definition\ModelQuery;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;

use function Mrap\GraphCool\pluralize;

class ExportModel extends ModelQuery
{

    public function __construct(string $model)
    {
        $plural = pluralize($model);
        $this->name = 'export' . $plural;
        $this->model = $model;

        $this->config = [
            'type' => Type::get('_FileExport'),
            'description' => 'Export ' . $plural . ' filtered by given where clauses as a spreadsheet file (XLSX, CSV or ODS).',
            'args' => $this->exportArgs($model),
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('export', $this->model);

        $type = $args['type'] ?? 'xlsx';
        $args['first'] = 1048575; // max number of rows allowed in excel - 1 (for headers)

        $data = DB::findAll(JwtAuthentication::tenantId(), $this->model, $args)->data;
        if ($data instanceof \Closure) {
            $data = $data();
        }
        return File::write(
            $this->model,
            $data ?? [],
            $args,
            $type
        );
    }
}
