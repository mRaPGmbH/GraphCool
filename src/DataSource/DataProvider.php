<?php
declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use stdClass;

abstract class DataProvider
{
    abstract public function migrate(): void;
    abstract public function load(string $name, string $id, ?string $resultType = 'DEFAULT'): ?stdClass;
    abstract public function loadAll(string $name, array $ids, ?string $resultType = 'DEFAULT'): ?array;
    abstract public function insert(string $name, array $data): stdClass;
    abstract public function update(string $name, array $data): stdClass;
    abstract public function findAll(string $name, array $args): stdClass;
    abstract public function delete(string $name, string $id): ?stdClass;
    abstract public function getMax(string $name, string $key): float|bool|int|string;


}