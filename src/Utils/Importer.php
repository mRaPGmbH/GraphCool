<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\FullTextIndex;
use Mrap\GraphCool\Definition\Job;

class Importer
{

    public function run(Job $job): array
    {
        $name = $job->data['name'];
        $args = $job->data['args'];
        [$create, $update, $errors] = File::read($name, $args);
        $inserted_ids = [];
        foreach ($create as $data) {
            $inserted_ids[] = DB::insert($job->tenantId, $name, $data)->id;
        }
        $updated_ids = [];
        foreach ($update as $data) {
            $updated_ids[] = DB::update($job->tenantId, $name, $data)->id;
        }
        $affected_ids = array_merge($inserted_ids, $updated_ids);
        foreach ($affected_ids as $id) {
            FullTextIndex::index($job->tenantId, $name, $id);
        }
        return [
            'success' => true,
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