<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlQueryBuilder;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\ModelQuery;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\FileImport2;
use Mrap\GraphCool\Utils\JwtAuthentication;
use RuntimeException;
use stdClass;

use function Mrap\GraphCool\model;
use function Mrap\GraphCool\pluralize;

class ImportModelPreview extends Query
{
    use ModelBased;

    public function __construct(?string $model = null)
    {
        if ($model === null) {
            throw new RuntimeException(__METHOD__.': parameter $model may not be null for ModelBased queries.');
        }

        $plural = pluralize($model);
        $this->name = 'import' . $plural . 'Preview';
        $this->model = $model;

        $this->config = [
            'type' => Type::get('_' . $model.'ImportPreview'),
            'description' => 'Get a preview of what an import of a list of ' .  $plural . ' from a spreadsheet would result in. Does not actually modify any data.',
            'args' => $this->importArgs($model)
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('import', $this->model);
        $total = 20;
        [$create, $update, $errors] = File::read($this->model, $args);
        $data = [];
        $max = $total - min((int)round($total/2), count($update));
        $i = 1;
        $ids = [];
        foreach ($update as $nr => $row) {
            $ids[$nr] = $row['id'];
        }
        $this->checkExistence($ids, $this->model, $errors);
        $model = model($this->model);
        foreach ($create as $row) {
            $data[] = $this->injectFakeValuesForImportPreview((object) $row, $model);
            if ($i >= $max) {
                break;
            }
            $i++;
        }
        foreach ($update as $row) {
            $data[] = $this->injectFakeValuesForImportPreview((object) $row, $model);
            if ($i >= $total) {
                break;
            }
            $i++;
        }
        return (object)[
            'data' => $data,
            'errors' => $errors
        ];
    }

    protected function checkExistence(array $ids, string $name, array &$errors): void
    {
        $model = model($name);
        $query = MysqlQueryBuilder::forModel($model, $name)->tenant(JwtAuthentication::tenantId());

        $query->select(['id'])
            ->where(['column' => 'id', 'operator' => 'IN', 'value' => $ids])
            ->withTrashed();

        $databaseIds = [];
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
            $databaseIds[] = $row->id;
        }
        $rowNumbers = array_flip($ids);
        foreach (array_diff($ids, $databaseIds) as $missingId) {
            $errors[] = [
                'row' => $rowNumbers[$missingId] ?? 0,
                'column' => FileImport2::$lastIdColumn,
                'value' => $missingId,
                'relation' => null,
                'field' => 'id',
                'ignored' => false,
                'message' => 'ID not found.'
            ];
        }
    }

    protected function injectFakeValuesForImportPreview(stdClass $row, Model $model): stdClass
    {
        foreach ($model as $key => $field) {
            if ($field instanceof Field && !$field->null) {
                $row->$key = match ($field->type) {
                    Type::ID => 'NEW',
                    Field::DELETED_AT, Field::UPDATED_AT, Field::CREATED_AT, Field::DATE_TIME, Field::DATE, Field::TIME => time() * 1000,
                    Field::AUTO_INCREMENT, Type::INT => 0,
                    Field::DECIMAL, Type::FLOAT => 0.0,
                    default => '',
                };
            }
        }
        return $row;
    }


}
