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
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Enums\LanguageEnumType;
use Mrap\GraphCool\Types\Enums\LocaleEnumType;
use Mrap\GraphCool\Types\Scalars\TimezoneOffset;
use stdClass;

class FileImport2
{

    protected string $file;

    public function import(string $name, array $args, int $index): array
    {
        $classname = '\\App\\Models\\' . $name;
        $model = new $classname();

        $columns = $args['columns'];
        $edgeColumns = $this->getEdgeColumns($model, $args);
        $input = $args['data_base64'] ?? $args['file'] ?? null;

        $reader = $this->getReader($input, $index);

        $sheets = $reader->getSheetIterator();
        if (iterator_count($sheets) > 1) {
            throw new Error('File contains multiple sheets, but only one sheet can be imported.');
        }
        $sheets->rewind();
        /** @var SheetInterface $sheet */
        $sheet = $sheets->current();
        $rows = $sheet->getRowIterator();
        if (iterator_count($rows) < 2) {
            throw new Error('Sheet must contain at least one row with headers, and one row with data.');
        }
        $rows->rewind();
        [$idKey, $mapping, $edgeMapping] = $this->getHeaderMapping($model, $rows->current(), $columns, $edgeColumns);
        $rows->next();
        $create = [];
        $update = [];
        $errors = [];
        $i = 2;
        while($rows->valid()) {
            $row = $rows->current();
            $cells = $row->getCells();
            if ($idKey === null) {
                $id = null;
            } else {
                $id = $cells[$idKey]->getValue() ?? null;
            }
            if (empty($id)) {
                $item = $this->getItem($model, $row, $mapping, $edgeMapping, $errors, $i);
                if ($item !== null && $item !== []) {
                    $create[] = $item;
                }
            } else {
                $item = [
                    'id' => $id,
                    'data' => $this->getItem($model, $row, $mapping, $edgeMapping, $errors, $i)
                ];
                if ($item !== null) {
                    $update[] = $item;
                }
            }
            $rows->next();
            $i++;
        }
        return [$create, $update, $errors];
    }

    protected function getItem(Model $model, Row $row, array $mapping, array $edgeMapping, array &$errors, int $rowNumber): ?array
    {
        $data = [];
        $cells = $row->getCells();
        $error = false;
        foreach ($model as $key => $field) {
            if ($field instanceof Field && isset($mapping[$key])) {
                $columnNumber = $mapping[$key];
                try {
                    $value = $this->convertField($field, $cells[$columnNumber]->getValue() ?? null);
                } catch (Error $e) {
                    if ($field->null === false) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'column' => $this->getColumn($columnNumber),
                            'value' => (string)($row[$mapping[$key]] ?? ''),
                            'relation' => null,
                            'field' => $key,
                            'ignored' => false,
                            'message' => $e->getMessage()
                        ];
                        $error = true;
                        continue;
                    }
                    $errors[] = [
                        'row' => $rowNumber,
                        'column' => $this->getColumn($columnNumber),
                        'value' => (string)($row[$mapping[$key]] ?? ''),
                        'relation' => null,
                        'field' => $key,
                        'ignored' => true,
                        'message' => $e->getMessage()
                    ];
                    continue;
                }
                if ($value === null && $field->null === false) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'column' => 'X',
                        'value' => (string)($row[$mapping[$key]] ?? ''),
                        'relation' => null,
                        'field' => $key,
                        'ignored' => false,
                        'message' => 'Mandatory field may not be empty'
                    ];
                    $error = true;
                    continue;
                }
                $data[$key] = $value;
            } elseif ($field instanceof Relation && isset($edgeMapping[$key])) {
                $data[$key] = $this->importRelation($field, $key, $cells, $edgeMapping[$key], $errors, $rowNumber);
            }
        }
        if ($error === true) {
            return null;
        }
        return $data;
    }

    protected function getColumn(int $i): string
    {
        $columns = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Z'];
        $count = count($columns);
        $value = $columns[$i % $count];
        if ($i > $count) {
            $value = $columns[floor($i / $count)] . $value;
        }
        return $value;
    }

    protected function importRelation(Relation $relation, string $property, array $cells, array $mapping, array &$errors, int $rowNumber): array
    {
        $return = [];
        foreach ($mapping as $map) {
            $index = $map['index'];
            $relatedId = $map['relatedId'];
            $edgeProperty = $map['edgeProperty'];
            $field = $relation->$edgeProperty;
            $cell = $cells[$index];
            if (!isset($return[$relatedId])) {
                $return[$relatedId] = [
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
            $return[$relatedId][$edgeProperty] = $this->convertField($field, $value);
        }
        return $return;
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
            case Field::COUNTRY_CODE:
                return Country::parse($value);
            case Field::TIMEZONE_OFFSET:
                $type = new TimezoneOffset();
                return $type->parseValue($value);
            case Field::LOCALE_CODE:
                $type = new LocaleEnumType();
                return $type->parseValue($value);
            case Field::CURRENCY_CODE:
                $type = new CurrencyEnumType();
                return $type->parseValue($value);
            case Field::LANGUAGE_CODE:
                $type = new LanguageEnumType();
                return $type->parseValue($value);
            case Field::ENUM:
                foreach ($field->enumValues as $enumValue) {
                    if (mb_strtolower($enumValue) === mb_strtolower($value)) {
                        return $enumValue;
                    }
                }
                throw new Error('Invalid value: ' . $value);
            default:
                return trim((string)$value);
        }
    }

    protected function getEdgeColumns(Model $model, array $args): array
    {
        $edgeColumns = [];
        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type === Relation::BELONGS_TO_MANY && isset($args[$key])) {
                $edgeColumns[$key] = $args[$key];
            }
        }
        return $edgeColumns;
    }

    protected function getReader(?string $input, int $index): ReaderInterface
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
        $reader = $this->getReaderObject($mimeType);
        if ($reader === null) {
            throw new Error('Could not import file: Unknown MimeType: '. $mimeType);
        }
        if ($reader instanceof CSVReader) {
            $reader = $this->detectSeparator($reader, $file);
        }
        $reader->open($file);
        $this->file = $file;
        return $reader;
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

    protected function getReaderObject(string $mimeType): ?ReaderInterface
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

    protected function getHeaderMapping(Model $model, Row $row, array $columns, array $edgeColumns): array
    {
        $mapping = [];
        $edgeMapping = [];
        $id = null;
        foreach ($row->getCells() as $key => $cell) {
            $header = $cell->getValue();
            foreach ($columns as $column) {
                if (($column['label'] ?? $column['column']) !== $header) {
                    continue;
                }
                $property = $column['column'];
                /** @var Field $field */
                $field = $model->$property ?? null;
                if ($field->type === Type::ID) {
                    $id = $key;
                    continue;
                }
                if ($field === null || !$field instanceof Field || $field->readonly === true) {
                    continue;
                }
                $mapping[$property] = $key;
            }
            foreach ($edgeColumns as $relationName => $edges) {
                foreach ($edges as $edge) {
                    foreach ($edge['columns'] as $column) {
                        $property = substr($column['column'], 1);
                        $field = $model->$relationName->$property ?? null;
                        if ($field === null || !$field instanceof Field || $field->readonly === true) {
                            continue;
                        }
                        if (isset($column['label']) && $column['label'] === $header) {
                            if (!isset($edgeMapping[$relationName])) {
                                $edgeMapping[$relationName] = [];
                            }
                            $edgeMapping[$relationName][] = [
                                'index' => $key,
                                'relatedId' => $edge['id'],
                                'edgeProperty' => $property,
                            ];

                            /*
                            $edgeMapping[$key] = [
                                'nodeProperty' => $relationName,
                                'relatedId' => $edge['id'],
                                'edgeProperty' => $property,
                            ];*/
                        }
                    }
                }
            }
        }
        return [$id, $mapping, $edgeMapping];
    }


}