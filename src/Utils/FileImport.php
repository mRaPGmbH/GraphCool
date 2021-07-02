<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Utils;


use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Reader as CSVReader;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use JsonException;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use stdClass;

class FileImport
{

    protected string $tenantId;
    protected string $name;
    protected Model $model;

    public function __construct(string $tenantId, string $name)
    {
        $this->tenantId = $tenantId;
        $this->name = $name;
        $classname = '\\App\\Models\\' . $name;
        $this->model = new $classname();
    }


    public function import(array $args): stdClass
    {
        $result = new stdClass();
        $result->updated_rows = 0;
        $result->updated_ids = [];
        $result->inserted_rows = 0;
        $result->inserted_ids = [];
        $result->failed_rows = 0;
        $result->failed_row_numbers = [];
        $edgeColumns = [];
        foreach ($this->model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type === Relation::BELONGS_TO_MANY && isset($args[$key])) {
                $edgeColumns[$key] = $args[$key];
            }
        }

        foreach ($this->importFile($args['data_base64'] ?? $args['file'] ?? null, $args['columns'], $edgeColumns, $rootValue['index'] ?? 0) as $i => $item) {
            $item = $this->convertItem($item);

            if (isset($item['id'])) {
                $outer = [
                    'id' => $item['id'],
                    'data' => $item
                ];
                unset($outer['data']['id']);
                $item = DB::update($this->tenantId, $this->name, $outer);
                if (is_null($item)) {
                    $result->failed_rows += 1;
                    $result->failed_row_numbers[] = ($i + 2);
                } else {
                    $result->updated_rows += 1;
                    $result->updated_ids[] = $item->id;
                }
            } else {
                $item = DB::insert($this->tenantId, $this->name, $item);
                $result->inserted_rows += 1;
                $result->inserted_ids[] = $item->id;
            }
        }
        $result->affected_rows = $result->inserted_rows + $result->updated_rows;
        $result->affected_ids = array_merge($result->inserted_ids, $result->updated_ids);
        return $result;
    }

    protected function convertItem(array $item): array
    {
        foreach ($item as $key => $value) {
            if (isset($this->model->$key) && $this->model->$key instanceof Field && $this->model->$key->readonly === false) {
                $item[$key] = $this->convertField($this->model->$key, $value);
            }
        }
        return $item;
    }

    protected function convertField(Field $field, $value): float|int|string|null
    {
        if (empty($value)) {
            return null;
        }
        switch ($field->type) {
            case Field::DATE:
            case Field::DATE_TIME:
            case Field::TIME:
                $carbon = Date::parse($value);
                if ($carbon === null) {
                    return null;
                }
                return (int)$carbon->getPreciseTimestamp(3);
            case Field::DECIMAL:
            case Type::FLOAT:
                return (float)$value;
            default:
                return trim((string)$value);
        }
    }

    public function importFile(?string $input, array $columns, array $edgeColumns, int $index): array
    {
        if ($input === null) {
            $tmp = $this->getFile($index);
            if ($tmp === null) {
                throw new Error('File is missing.');
            }
            $file = $tmp['tmp_name'];
            $mimeType = $tmp['type'];
            if ($mimeType === 'application/octet-stream') {
                $mimeType = mime_content_type($file);
            }
        } else {
            $file = tempnam(sys_get_temp_dir(), 'import');
            file_put_contents($file, base64_decode($input));
            $mimeType = mime_content_type($file);
        }

        $reader = $this->getReader($mimeType);
        if ($reader === null) {
            throw new Error('Could not import file: Unknown MimeType: '. $mimeType);
        }
        if ($reader instanceof CSVReader) {
            $reader = $this->detectSeparator($reader, $file);
        }

        $reader->open($file);
        $result = [];

        /** @var SheetInterface $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            $firstRow = true;
            $mapping = [];
            $edgeMapping = [];
            /** @var Row $row */
            foreach ($sheet->getRowIterator() as $row) {
                if ($firstRow) {
                    [$mapping, $edgeMapping] = $this->getHeaderMapping($row, $columns, $edgeColumns);
                    $firstRow = false;
                    continue;
                }
                $item = [];
                foreach ($row->getCells() as $key => $cell) {
                    if (isset($mapping[$key])) {
                        $property = $mapping[$key];
                        /** @var Field $field */
                        $field = $this->model->$property;
                        $value = $cell->getValue();
                        if (empty($value)) {
                            $value = $field->default ?? null;
                        }
                        $item[$property] = $value;
                        if ($property === 'id' && empty($item[$property])) {
                            unset($item[$property]);
                        }
                    } elseif (isset($edgeMapping[$key])) {
                        $property = $edgeMapping[$key]['nodeProperty'];
                        $relatedId = $edgeMapping[$key]['relatedId'];
                        $edgeProperty = $edgeMapping[$key]['edgeProperty'];
                        $field = $this->model->$property->$edgeProperty;
                        if (!isset($item[$property])) {
                            $item[$property] = [];
                        }
                        if (!isset($item[$property][$relatedId])) {
                            $item[$property][$relatedId] = [
                                'where' => [
                                    'column' => 'id',
                                    'operator' => '=',
                                    'value' => $relatedId
                                ]
                            ];
                        }
                        $value = $cell->getValue();
                        if (empty($value)) {
                            $value = $field->default ?? null;
                        }
                        $item[$property][$relatedId][$edgeProperty] = $this->convertField($field, $value);
                    }
                }
                $result[] = $item;
            }
            break; // only the first sheet will be used
        }
        $reader->close();
        unlink($file);
        return $result;
    }

    protected function getHeaderMapping(Row $row, array $columns, array $edgeColumns): array
    {
        $mapping = [];
        $edgeMapping = [];
        foreach ($row->getCells() as $key => $cell) {
            $header = $cell->getValue();
            foreach ($columns as $column) {
                $property = $column['column'];
                /** @var Field $field */
                $field = $this->model->$property ?? null;
                if ($field === null || !$field instanceof Field || ($field->readonly === true && $field->type !== Type::ID)) {
                    continue;
                }
                if (isset($column['label']) && $column['label'] === $header) {
                    $mapping[$key] = $property;
                }
            }
            foreach ($edgeColumns as $relationName => $edges) {
                foreach ($edges as $edge) {
                    foreach ($edge['columns'] as $column) {
                        $property = substr($column['column'], 1);
                        $field = $this->model->$relationName->$property ?? null;
                        if ($field === null || !$field instanceof Field || $field->readonly === true) {
                            continue;
                        }
                        if (isset($column['label']) && $column['label'] === $header) {
                            $edgeMapping[$key] = [
                                'nodeProperty' => $relationName,
                                'relatedId' => $edge['id'],
                                'edgeProperty' => $property,
                            ];
                        }
                    }
                }
            }
        }
        return [$mapping, $edgeMapping];
    }

    protected function getFile(int $index): ?array
    {
        if (!isset($_REQUEST['map'])) {
            throw new Error('Neither data_base64 nor file received.');
        }

        try {
            $map = json_decode($_REQUEST['map'], true, 512, JSON_THROW_ON_ERROR);
        } catch(JsonException $e) {
            throw new Error('Could not parse file map - not a valid JSON.');
        }
        $fileNumber = $this->findInMap($map, $index);
        return $_FILES[$fileNumber] ?? null;
    }

    protected function findInMap(array $map, int $index): ?int
    {
        $key = 'variables.file';
        foreach ($map as $fileNumber => $variableNames) {
            foreach ($variableNames as $variableName) {
                if ($variableName === $key || $variableName === $index.'.'.$key) {
                    return $fileNumber;
                }
            }
        }
        return null;
    }

    protected function getReader(string $mimeType): ?ReaderInterface
    {
        return match ($mimeType) {
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ReaderEntityFactory::createXLSXReader(),
            'application/vnd.oasis.opendocument.spreadsheet' => ReaderEntityFactory::createODSReader(),
            'text/csv', 'text/plain', 'application/csv' => ReaderEntityFactory::createCSVReader(),
            default => null
        };
    }

    protected function detectSeparator(CSVReader $reader, string $file): CSVReader
    {
        $handle = fopen($file, 'rb');
        $sample = fread($handle, 100);
        fclose($handle);

        if (strlen(str_replace(';', '', $sample)) < strlen(str_replace(',', '', $sample))) {
            $reader->setFieldDelimiter(';');
        }

        return $reader;
    }

}