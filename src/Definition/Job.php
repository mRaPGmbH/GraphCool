<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

use stdClass;

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

    public static function allStatuses(): array
    {
        return [
            self::NEW,
            self::RUNNING,
            self::FINISHED,
            self::FAILED
        ];
    }

    public static function parse(stdClass $dto): stdClass
    {
        if (isset($dto->result)) {
            $result = (object)json_decode($dto->result, false, 512, JSON_THROW_ON_ERROR);
            $dto->result = static::parseResult($dto->worker, $result);
        }
        foreach (['run_at', 'created_at', 'started_at', 'finished_at'] as $date) {
            if (($dto->$date ?? null) !== null) {
                $dto->$date = strtotime($dto->$date) * 1000;
            }
        }
        return $dto;
    }

    protected static function parseResult(string $worker, ?stdClass $result): ?stdClass
    {
        if ($result === null) {
            return null;
        }
        return match($worker) {
            'importer' => static::parseImporterResult($result),
            default => $result,
        };
    }

    protected static function parseImporterResult(stdClass $result): stdClass
    {
        $result->inserted_rows ??= 0;
        $result->inserted_ids ??= [];
        $result->updated_rows ??= 0;
        $result->updated_ids ??= [];
        $result->affected_rows ??= $result->inserted_rows + $result->updated_rows;
        $result->affected_ids ??= array_merge($result->inserted_ids, $result->updated_ids);
        $result->failed_rows ??= 0;
        $result->failed_row_numbers ??= [];
        return $result;
    }

}
