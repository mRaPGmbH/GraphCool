<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Utils;


use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Reader as CSVReader;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use JsonException;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\GraphCool;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use RuntimeException;
use stdClass;
use Throwable;

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
        try {
            switch ($field->type) {
                case Field::DATE:
                case Field::DATE_TIME:
                case Field::TIME:
                    $carbon = $this->convertDate($value);
                    if ($carbon === null) {
                        return null;
                    }
                    return (int)$carbon->getPreciseTimestamp(3);
                case Field::DECIMAL:
                case Type::FLOAT:
                    return (float)$value;
                default:
                    return (string)$value;
            }
        } catch (Throwable $e) {
            // date parsing failed
            return null;
        }
    }

    protected function convertDate(mixed $value): ?Carbon
    {
        $timezone = TimeZone::get();
        if ($timezone === '+00:00') {
            $timezone = null;
        }

        try {
            return Carbon::parse($value, $timezone);
        } catch (Throwable $e) {
            // ignore - non-parseable formats are handled below
        }

        $value = (string)$value;
        $fragments = [
            'd' => '[0-9]{2}',
            'j' => '[0-9]{1,2}',

            'm' => '[0-9]{2}',
            'n' => '[0-9]{1,2}',

            'Y' => '[0-9]{4}',
            'y' => '[0-9]{2}',

            'H' => '[0-9]{2}',
            'h' => '[0-9]{1,2}',

            'i' => '[0-9]{2}',
            's' => '[0-9]{2}',

            'a' => '(am|pm)',
            'A' => '(AM|PM)',
        ];

        $formats = [
            'd.m.Y',
            'd.m.Y H:i',
            'd.m.Y H:i:s',
            'd.m.Y G:i',
            'd.m.Y G:i:s',

            'd.m.y',
            'd.m.y H:i',
            'd.m.y H:i:s',
            'd.m.y G:i',
            'd.m.y G:i:s',

            'j.n.Y',
            'j.n.Y H:i',
            'j.n.Y H:i:s',
            'j.n.Y G:i',
            'j.n.Y G:i:s',

            'j.n.y',
            'j.n.y H:i',
            'j.n.y H:i:s',
            'j.n.y G:i',
            'j.n.y G:i:s',

            'Y-m-d',
            'Y-m-d H:i',
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',

            'm/d/Y',
            'm/d/Y h:ia',
            'm/d/Y h:i a',
            'm/d/Y h:iA',
            'm/d/Y h:i A',
            'm/d/Y h:i:sa',
            'm/d/Y h:i:s a',
            'm/d/Y h:i:sA',
            'm/d/Y h:i:s A',
            'm/d/Y g:ia',
            'm/d/Y g:i a',
            'm/d/Y g:iA',
            'm/d/Y g:i A',
            'm/d/Y g:i:sa',
            'm/d/Y g:i:s a',
            'm/d/Y g:i:sA',
            'm/d/Y g:i:s A',
            'm/d/Y H:i',
            'm/d/Y H:i:s',
            'm/d/Y G:i',
            'm/d/Y G:i:s',

            'm/d/y',
            'm/d/y h:ia',
            'm/d/y h:i a',
            'm/d/y h:iA',
            'm/d/y h:i A',
            'm/d/y h:i:sa',
            'm/d/y h:i:s a',
            'm/d/y h:i:sA',
            'm/d/y h:i:s A',
            'm/d/y g:ia',
            'm/d/y g:i a',
            'm/d/y g:iA',
            'm/d/y g:i A',
            'm/d/y g:i:sa',
            'm/d/y g:i:s a',
            'm/d/y g:i:sA',
            'm/d/y g:i:s A',
            'm/d/y H:i',
            'm/d/y H:i:s',
            'm/d/y G:i',
            'm/d/y G:i:s',

            'n/j/Y',
            'n/j/Y h:ia',
            'n/j/Y h:i a',
            'n/j/Y h:iA',
            'n/j/Y h:i A',
            'n/j/Y h:i:sa',
            'n/j/Y h:i:s a',
            'n/j/Y h:i:sA',
            'n/j/Y h:i:s A',
            'n/j/Y g:ia',
            'n/j/Y g:i a',
            'n/j/Y g:iA',
            'n/j/Y g:i A',
            'n/j/Y g:i:sa',
            'n/j/Y g:i:s a',
            'n/j/Y g:i:sA',
            'n/j/Y g:i:s A',
            'n/j/Y H:i',
            'n/j/Y H:i:s',
            'n/j/Y G:i',
            'n/j/Y G:i:s',

            'n/j/y',
            'n/j/y h:ia',
            'n/j/y h:i a',
            'n/j/y h:iA',
            'n/j/y h:i A',
            'n/j/y h:i:sa',
            'n/j/y h:i:s a',
            'n/j/y h:i:sA',
            'n/j/y h:i:s A',
            'n/j/y g:ia',
            'n/j/y g:i a',
            'n/j/y g:iA',
            'n/j/y g:i A',
            'n/j/y g:i:sa',
            'n/j/y g:i:s a',
            'n/j/y g:i:sA',
            'n/j/y g:i:s A',
            'n/j/y H:i',
            'n/j/y H:i:s',
            'n/j/y G:i',
            'n/j/y G:i:s',

            'H:i',
            'H:i:s',
            'G:i',
            'G:i:s',

            'h:ia',
            'h:i a',
            'h:iA',
            'h:i A',
            'h:i:sa',
            'h:i:s a',
            'h:i:sA',
            'h:i:s A',

            'g:ia',
            'g:i a',
            'g:iA',
            'g:i A',
            'g:i:sa',
            'g:i:s a',
            'g:i:sA',
            'g:i:s A',
        ];

        foreach ($formats as $format) {
            $pattern = '/^' . str_replace(array_keys($fragments), array_values($fragments), $format) . '$/';
            if (preg_match($pattern, $value)) {
                return Carbon::createFromFormat($format, $value, $timezone);
            }
        }
        $e = new RuntimeException('Could not parse date: '.$value);
        GraphCool::sentryCapture($e);
        return null;
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