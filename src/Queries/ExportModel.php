<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Queries;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\Exporter;
use Mrap\GraphCool\Utils\JwtAuthentication;
use RuntimeException;

use function Mrap\GraphCool\pluralize;

class ExportModel extends Query
{
    use ModelBased;

    public function __construct(?string $model = null)
    {
        if ($model === null) {
            throw new RuntimeException(__METHOD__.': parameter $model may not be null for ModelBased queries.');
        }

        $plural = pluralize($model);
        $this->name = 'export' . $plural;
        $this->model = $model;

        $this->config = [
            'type' => Type::fileExport(),
            'description' => 'Export ' . $plural . ' filtered by given where clauses as a spreadsheet file (XLSX, CSV or ODS).',
            'args' => $this->exportArgs($model),
        ];
    }

    /**
     * @throws Error
     */
    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('export', $this->model);
        $type = $args['type'] ?? 'csv';

        $data = (new Exporter())->loadData(JwtAuthentication::tenantId(), $this->model, $args);

        return File::write(
            $this->model,
            $data ?? [],
            $args,
            $type
        );
    }
}
