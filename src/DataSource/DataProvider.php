<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use Mrap\GraphCool\Definition\Job;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\Result;
use stdClass;

interface DataProvider
{
    public function migrate(): void;

    public function load(?string $tenantId, string $id, ?string $resultType = Result::DEFAULT): ?stdClass;

    /**
     * @param string|null $tenantId
     * @param string[] $ids
     * @param string|null $resultType
     * @return mixed[]
     */
    public function loadNodes(?string $tenantId, array $ids, ?string $resultType = Result::DEFAULT): array;

    public function loadEdges(array $ids): array;

    /**
     * @param string $tenantId
     * @param string $name
     * @param mixed[] $data
     * @return stdClass|null
     */
    public function insert(string $tenantId, string $name, array $data): ?stdClass;

    /**
     * @param string $tenantId
     * @param string $name
     * @param mixed[] $data
     * @return stdClass|null
     */
    public function update(string $tenantId, string $name, array $data): ?stdClass;

    /**
     * @param string $tenantId
     * @param string $name
     * @param mixed[] $data
     * @return stdClass
     */
    public function updateMany(string $tenantId, string $name, array $data): stdClass;

    /**
     * @param string|null $tenantId
     * @param string $name
     * @param mixed[] $args
     * @return stdClass
     */
    public function findNodes(?string $tenantId, string $name, array $args): stdClass;

    public function findEdges(?string $tenantId, string $nodeId, Relation $relation, array $args): array|stdClass;

    public function delete(string $tenantId, string $name, string $id): ?stdClass;

    public function restore(string $tenantId, string $name, string $id): stdClass;

    public function getMax(?string $tenantId, string $name, string $key): float|bool|int|string;

    public function getSum(?string $tenantId, string $name, string $key): float|bool|int|string;

    public function getCount(?string $tenantId, string $name): int;

    public function increment(string $tenantId, string $key, int $min = 0, bool $transaction = true): int;

    public function addJob(string $tenantId, string $worker, ?string $model, ?array $data = null): string;

    public function takeJob(): ?Job;

    public function finishJob(string $id, ?array $result = null, bool $failed = false): void;

    public function getJob(?string $tenantId, string $name, string $id): ?stdClass;

    public function findJobs(?string $tenantId, string $name, array $args): stdClass;

    public function findHistory(?string $tenantId, array $args): stdClass;

}
