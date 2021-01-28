<?php


namespace Mrap\GraphCool\DataSource;

use stdClass;

abstract class DataProvider
{
    abstract public function migrate(): void;
    abstract public function load(string $name, string $id): ?stdClass;
    abstract public function loadAll(string $name, array $ids): ?array;
    abstract public function insert(string $name, array $data): stdClass;
    abstract public function update(string $name, array $data): stdClass;

}