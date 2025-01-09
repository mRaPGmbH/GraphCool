<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Closure;
use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Definition\Job;
use Mrap\GraphCool\Exception\OutOfMemoryException;
use stdClass;

class Exporter
{

    public function run(Job $job): array
    {
        $name = $job->data['name'];
        $args = $job->data['args'];
        $startAt = $job->data['startAt'] ?? 1;
        $num = $job->data['num'] ?? 1;
        $jwt = $job->data['jwt'];
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $jwt;
        $type = $args['type'] ?? 'csv';
        $postFix = '';

        $data = $this->loadData($job->tenantId, $name, $args, $startAt, false);

        $closure = function() {};

        if ($startAt !== null) {
            $postFix = '_Part-' . $num;
            $jobData = $job->data;
            $jobData['startAt'] = $startAt;
            $jobData['num'] = $num + 1;
            $closure = function() use($job, $name, $jobData) {
                DB::addJob($job->tenantId, $job->worker, $name, $jobData);
            };
        } elseif ($num > 1) {
            $postFix = '_Part-' . $num;
        }

        $file = File::write(
            $name,
            $data ?? [],
            $args,
            $type,
            $postFix
        );
        $data = File::store($name, 'export', $type , get_object_vars($file));

        $closure(); // only insert new job, after we are sure the current job is a success

        return [
            'success' => true,
            'filename' => $data->filename,
            'mime_type' => $data->mime_type,
            'url' => $data->url,
            'filesize' => $data->filesize
        ];
    }

    public function loadData(string $tenantId, string $name, array $args, ?int &$startAt = 1, bool $throwError = true): array
    {
        memory_reset_peak_usage();
        $data = [];
        $unsets = null;
        $args['first'] = 100;
        $pages = DB::findNodes($tenantId, $name, $args)->paginatorInfo->lastPage;

        try {
            for ($page = $startAt; $page <= $pages; $page++) {
                $startAt = null;
                $args['page'] = $page;

                $rows = DB::findNodes($tenantId, $name, $args)->data;
                if ($rows instanceof Closure) {
                    $rows = $rows();
                }
                if (memory_get_peak_usage() > 104857600) { // 100 MB
                    $startAt = $page;
                    throw new OutOfMemoryException();
                }
                foreach ($rows as $row) {
                    if ($unsets === null) {
                        $unsets = $this->getUnsets($row, $args);
                    }
                    foreach ($unsets as $unset) {
                        unset($row->$unset);
                    }
                    $data[] = $row;
                }
                if ($page < $pages && memory_get_peak_usage() > 104857600) { // 100 MB
                    $startAt = $page+1;
                    throw new OutOfMemoryException();
                }
            }
        } catch (OutOfMemoryException) {
            if ($throwError) {
                throw new Error('Export too big - out of memory');
            }
        }
        return $data;
    }

    protected function getUnsets(stdClass $row, array $args): array
    {
        $unsets = [];
        foreach ($row as $key => $v) {
            if ($v instanceof Closure && !isset($args[$key])) {
                $unsets[] = $key;
            }
        }
        return $unsets;
    }


}
