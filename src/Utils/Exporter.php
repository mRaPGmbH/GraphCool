<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Closure;
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
        $type = $args['type'] ?? 'csv';

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $jwt;

        $data = DB::findNodes($job->tenantId, $name, $args)->data;
        if ($data instanceof Closure) {
            $data = $data();
        }

        $file = File::write(
            $name,
            $data ?? [],
            $args,
            $type
        );
        $data = File::store($name, 'export', $type , get_object_vars($file));
        return [
            'success' => true,
            'filename' => $data->filename,
            'mime_type' => $data->mime_type,
            'url' => $data->url,
            'filesize' => $data->filesize
        ];
    }
}
