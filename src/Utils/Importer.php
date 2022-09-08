<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlQueryBuilder;
use Mrap\GraphCool\Definition\Job;
use function Mrap\GraphCool\model;

class Importer
{

    public function run(Job $job): array
    {
        $name = $job->data['name'];
        $args = $job->data['args'];
        [$create, $update, $errors] = File::read($name, $args);
        $ids = [];
        foreach ($update as $nr => $row) {
            $ids[$nr] = $row['id'];
        }
        $this->checkExistence($ids, $name, $errors);
        foreach ($errors as $error) {
            if ($error['ignored'] === false) {
                return [
                    'success' => false,
                    'inserted_rows' => 0,
                    'inserted_ids' => [],
                    'updated_rows' => 0,
                    'updated_ids' => [],
                    'affected_rows' => 0,
                    'affected_ids' => [],
                    'failed_rows' => count($create) + count($update),
                    'failed_row_numbers' => array_merge(array_keys($create), array_keys($update)),
                    'errors' => $errors
                ];
            }
        }
        $failed_rows = [];
        $inserted_ids = [];
        foreach ($create as $row => $data) {
            try {
                $inserted_ids[] = DB::insert($job->tenantId, $name, $data)->id;
            } catch (\Throwable $e) {
                ErrorHandler::sentryCapture($e);
                $failed_rows[] = $row;
                $errors[] = [
                    'row' => $row,
                    'column' => '?',
                    'value' => '',
                    'relation' => null,
                    'field' => 'unknown',
                    'ignored' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }
        $updated_ids = [];
        foreach ($update as $row => $data) {
            try {
                $updated_ids[] = DB::update($job->tenantId, $name, $data)->id;
            } catch (\Throwable $e) {
                ErrorHandler::sentryCapture($e);
                $failed_rows[] = $row;
                $errors[] = [
                    'row' => $row,
                    'column' => '?',
                    'value' => '',
                    'relation' => null,
                    'field' => 'unknown',
                    'ignored' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }
        $affected_ids = array_merge($inserted_ids, $updated_ids);
        return [
            'success' => true,
            'inserted_rows' => count($inserted_ids),
            'inserted_ids' => $inserted_ids,
            'updated_rows' => count($updated_ids),
            'updated_ids' => $updated_ids,
            'affected_rows' => count($affected_ids),
            'affected_ids' => $affected_ids,
            'failed_rows' => count($failed_rows),
            'failed_row_numbers' => $failed_rows,
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

}
