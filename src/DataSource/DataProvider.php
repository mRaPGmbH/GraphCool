<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use stdClass;

interface DataProvider
{
    public function migrate(): void;

    public function load(?string $tenantId, string $name, string $id, ?string $resultType = 'DEFAULT'): ?stdClass;

    public function loadAll(?string $tenantId, string $name, array $ids, ?string $resultType = 'DEFAULT'): array;

    public function insert(string $tenantId, string $name, array $data): stdClass;

    public function update(string $tenantId, string $name, array $data): ?stdClass;

    public function updateMany(string $tenantId, string $name, array $data): stdClass;

    public function findAll(?string $tenantId, string $name, array $args): stdClass;

    public function delete(string $tenantId, string $name, string $id): ?stdClass;

    public function restore(string $tenantId, string $name, string $id): stdClass;

    public function getMax(?string $tenantId, string $name, string $key): float|bool|int|string;
}