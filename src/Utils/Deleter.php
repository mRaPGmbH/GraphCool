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

class Deleter
{

    public function run(Job $job): array
    {
        $name = $job->data['name'];
        $args = $job->data['args'];
        $jwt = $job->data['jwt'];
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $jwt;
        JwtAuthentication::overrideTenantId($job->tenantId);

        $success = DB::deleteMany($job->tenantId, $name, $args);

        return [
            'success' => $success,
        ];
    }

}
