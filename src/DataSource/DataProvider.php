<?php
declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Utils\FileImport;
use stdClass;

abstract class DataProvider
{
    abstract public function migrate(): void;
    abstract public function load(?string $tenantId, string $name, string $id, ?string $resultType = 'DEFAULT'): ?stdClass;
    abstract public function loadAll(?string $tenantId, string $name, array $ids, ?string $resultType = 'DEFAULT'): array;
    abstract public function insert(string $tenantId, string $name, array $data): stdClass;
    abstract public function update(string $tenantId, string $name, array $data): ?stdClass;
    abstract public function updateMany(string $tenantId, string $name, array $data): stdClass;
    abstract public function findAll(?string $tenantId, string $name, array $args): stdClass;
    abstract public function delete(string $tenantId, string $name, string $id): ?stdClass;
    abstract public function restore(string $tenantId, string $name, string $id): stdClass;
    abstract public function getMax(?string $tenantId, string $name, string $key): float|bool|int|string;

    public function import(string $tenantId, string $name, array $args): stdClass
    {
        $classname = '\\App\\Models\\' . $name;
        $model = new $classname();
        $importer = new FileImport();
        $result = new stdClass();
        $result->updated_rows = 0;
        $result->updated_ids = [];
        $result->inserted_rows = 0;
        $result->inserted_ids = [];
        $result->failed_rows = 0;
        $result->failed_row_numbers = [];
        foreach ($importer->import($args['data_base64'] ?? $args['file'] ?? null, $args['columns'], $rootValue['index'] ?? 0) as $i => $item) {
            $item = $this->convertItem($model, $item);
            if (isset($item['id'])) {
                $item = DB::update($tenantId, $name, $item);
                if (is_null($item)) {
                    $result->failed_rows += 1;
                    $result->failed_row_numbers[] = ($i + 2);
                } else {
                    $result->updated_rows += 1;
                    $result->updated_ids[] = $item->id;
                }
            } else {
                $item = DB::insert($tenantId, $name, $item);
                $result->inserted_rows += 1;
                $result->inserted_ids[] = $item->id;
            }
        }
        $result->affected_rows = $result->inserted_rows + $result->updated_rows;
        $result->affected_ids = array_merge($result->inserted_ids, $result->updated_ids);
        return $result;
    }

    protected function convertItem(Model $model, array $item): array
    {
        foreach ($item as $key => $value) {
            if (isset($model->$key) && $model->$key instanceof Field) {
                $item[$key] = $this->convertInputTypeToDatabase($model->$key, $value);
            }
        }
        return $item;
    }

    protected function convertInputTypeToDatabase(Field $field, $value): float|int|string|null
    {
        if ($field->null === false && $value === null) {
            $value = $field->default ?? null;
            if (is_null($value)) {
                return null;
            }
        }
        return match ($field->type) {
            default => (string)$value,
            Field::DATE, Field::DATE_TIME, Field::TIME, Field::TIMEZONE_OFFSET, Type::BOOLEAN, Type::INT => (int)$value,
            Type::FLOAT => (float)$value,
            Field::DECIMAL => (int)(round($value * (10 ** $field->decimalPlaces))),
        };
    }

}