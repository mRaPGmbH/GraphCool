<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use stdClass;

interface DataProvider
{
    public function migrate(): void;

    public function load(?string $tenantId, string $name, string $id, ?string $resultType = 'DEFAULT'): ?stdClass;

    /**
     * @param string|null $tenantId
     * @param string $name
     * @param string[] $ids
     * @param string|null $resultType
     * @return mixed[]
     */
    public function loadAll(?string $tenantId, string $name, array $ids, ?string $resultType = 'DEFAULT'): array;

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
    public function findAll(?string $tenantId, string $name, array $args): stdClass;

    public function delete(string $tenantId, string $name, string $id): ?stdClass;

    public function restore(string $tenantId, string $name, string $id): stdClass;

    public function getMax(?string $tenantId, string $name, string $key): float|bool|int|string;

    public function getSum(?string $tenantId, string $name, string $key): float|int;

    public function getCount(?string $tenantId, string $name): int;

    public function increment(string $tenantId, string $key, int $min = 0): int;
}