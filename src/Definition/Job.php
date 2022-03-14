<?php

namespace Mrap\GraphCool\Definition;

class Job
{
    const NEW = 'NEW';
    const RUNNING = 'RUNNING';
    const FINISHED = 'FINISHED';
    const FAILED = 'FAILED';
    const ALWAYS = 'ALWAYS';
    const HOURLY = 'HOURLY';
    const DAILY = 'DAILY';
    const WEEKLY = 'WEEKLY';

    public string $id;
    public string $tenantId;
    public string $worker;
    public string $status;
    public ?array $data;
    public ?array $result;

}