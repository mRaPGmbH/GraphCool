<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Definition\Job;

class Exporter
{

    public function run(Job $job): array
    {
        $name = $job->data['name'];
        $args = $job->data['args'];
        $jwt = $job->data['jwt'];
        $type = $args['type'] ?? 'xlsx';

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $jwt;

        $file = File::write(
            $name,
            DB::findAll($job->tenantId, $name, $args)->data ?? [],
            $args,
            $type
        );
        $id = File::store($name, 'export', $type , get_object_vars($file));
        return ['file_id' => $id];
    }
}