<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Reader as CSVReader;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlQueryBuilder;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Enums\LanguageEnumType;
use Mrap\GraphCool\Types\Enums\LocaleEnumType;
use Mrap\GraphCool\Types\Scalars\TimezoneOffset;
use Mrap\GraphCool\Types\Type;
use RuntimeException;
use function Mrap\GraphCool\model;

class FileImport2
{

    protected string $file;

    public static string $lastIdColumn;

    /**
     * @param string $name
     * @param mixed[] $args
     * @return array[]
     * @throws Error
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    public function import(string $name, array $args): array
    {
        $errors = [];
        $create = [];
        $update = [];

        $model = model($name);

        $columns = $args['columns'];
        $edgeColumns = $this->getEdgeColumns($model, $args);
        $input = $args['data_base64'] ?? $args['file'] ?? null;

        $reader = $this->getReader($input);

        $sheets = $reader->getSheetIterator();
        if (iterator_count($sheets) > 1) {
            $errors[] = [
                'row' => 0,
                'column' => '?',
                'value' => '?',
                'relation' => null,
                'field' => '?',
                'ignored' => false,
                'message' => 'File contains multiple sheets, but only one sheet can be imported.',
            ];
            unlink($this->file);
            return [$create, $update, $errors];
        }
        $sheets->rewind();
        /** @var SheetInterface $sheet */
        $sheet = $sheets->current();
        $rows = $sheet->getRowIterator();
        $rows->rewind();
        [$idKey, $mapping, $edgeMapping] = $this->getHeaderMapping($model, $rows->current(), $columns, $edgeColumns, $errors);
        static::$lastIdColumn = $this->getColumn($idKey ?? 0);

        $rows->next();
        if (!$rows->valid()) {
            $errors[] = [
                'row' => 2,
                'column' => '?',
                'value' => '?',
                'relation' => null,
                'field' => '?',
                'ignored' => false,
                'message' => 'Sheet must contain at least one row with headers, and one row with data.',
            ];
            unlink($this->file);
            return [$create, $update, $errors];
        }
        $i = 2;
        while ($rows->valid()) {
            $row = $rows->current();
            $cells = $row->getCells();
            if ($idKey === null) {
                $id = null;
            } else {
                $id = $cells[$idKey]->getValue() ?? null;
            }
            $item = $this->getItem($model, $row, $mapping, $edgeMapping, $errors, $i);
            if ($item !== null && $item !== []) {
                if (empty($id)) {
                    $create[$i] = $item;
                } else {
                    $update[$i] = [
                        'id' => $id,
                        'data' => $item
                    ];
                }
            }
            $rows->next();
            $i++;
        }
        unlink($this->file);
        return [$create, $update, $errors];
    }

    /**
     * @param Model $model
     * @param mixed[] $args
     * @return mixed[]
     */
    protected function getEdgeColumns(Model $model, array $args): array
    {
        $edgeColumns = [];
        foreach (get_object_vars($model) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type === Relation::BELONGS_TO_MANY && isset($args[$key])) {
                $edgeColumns[$key] = $args[$key];
            }
        }
        return $edgeColumns;
    }

    protected function getReader(mixed $input): ReaderInterface
    {
        if (is_array($input)) {
            $file = $input['tmp_name'] ?? null;
        } elseif (is_string($input)) {
            $file = tempnam(sys_get_temp_dir(), 'import');
            if ($file === false) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not save temporary file for import.');
                // @codeCoverageIgnoreEnd
            }
            $data = base64_decode($input, true);
            if ($data === false) {
                throw new Error('data_base64 could not be decoded.');
            }
            file_put_contents($file, $data);
        }
        if ($input === null || $file === null) {
            throw new Error('Neither data_base64 nor file received.');
        }

        $mimeType = mime_content_type($file);
        if ($mimeType === false) {
            // @codeCoverageIgnoreStart
            throw new Error('Could not import file: MimeType could not be detected.');
            // @codeCoverageIgnoreEnd
        }
        $reader = $this->getReaderObject($mimeType);
        if ($reader === null) {
            throw new Error('Could not import file: Unknown MimeType: ' . $mimeType);
        }
        $reader->setShouldFormatDates(true); // get text-string for dates, instead of DateTime object
        if ($reader instanceof CSVReader) {
            $reader = $this->detectSeparator($reader, $file);
        }
        $reader->open($file);
        $this->file = $file;
        return $reader;
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
        if ($handle === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('CSV separator detection failed to open file ' . $file);
            // @codeCoverageIgnoreEnd
        }
        $sample = fread($handle, 100);
        if ($sample === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('CSV separator detection failed to read sample from file ' . $file);
            // @codeCoverageIgnoreEnd
        }
        fclose($handle);
        if (strlen(str_replace(';', '', $sample)) < strlen(str_replace(',', '', $sample))) {
            $reader->setFieldDelimiter(';');
        }
        return $reader;
    }

    /**
     * @param Model $model
     * @param Row $row
     * @param array[] $columns
     * @param array[] $edgeColumns
     * @param array $errors
     * @return mixed[]
     */
    protected function getHeaderMapping(Model $model, Row $row, array $columns, array $edgeColumns, array &$errors): array
    {
        $mapping = [];
        $edgeMapping = [];
        $id = null;
        $headers = [];
        foreach ($row->getCells() as $key => $cell) {
            $v = $cell->getValue();
            if ($v === null || $v === '') {
                continue;
            }
            $v = (string)$v;
            if (array_key_exists($v, $headers)) {
                $errors[] = [
                    'row' => 1,
                    'column' => $this->getColumn($key),
                    'value' => $v,
                    'relation' => null,
                    'field' => $key,
                    'ignored' => true,
                    'message' => 'Duplicate column.',
                ];
            } else {
                $headers[$v] = $key;
            }
        }
        foreach ($columns as $column) {
            $property = $column['column'];
            $mappingHeader = $column['label'] ?? $property;
            if (!array_key_exists($mappingHeader, $headers)) {
                $errors[] = [
                    'row' => 1,
                    'column' => '?',
                    'value' => $mappingHeader,
                    'relation' => null,
                    'field' => $property,
                    'ignored' => true,
                    'message' => 'Missing column.',
                ];
                continue;
            }
            $key = $headers[$mappingHeader];
            $field = $model->$property ?? null;
            if ($field instanceof Field && $field->type === Type::ID) {
                $id = $key;
                continue;
            }
            if ($field === null || !$field instanceof Field) {
                $errors[] = [
                    'row' => 1,
                    'column' => $this->getColumn($key),
                    'value' => $mappingHeader,
                    'relation' => null,
                    'field' => $property,
                    'ignored' => true,
                    'message' => 'Unknown field in mapping.',
                ];
            } elseif ($field->readonly === true) {
                $errors[] = [
                    'row' => 1,
                    'column' => $this->getColumn($key),
                    'value' => $mappingHeader,
                    'relation' => null,
                    'field' => $property,
                    'ignored' => true,
                    'message' => 'Field is readonly.',
                ];
            } else {
                $mapping[$property] = $key;
            }
        }
        foreach ($edgeColumns as $relationName => $edges) {
            $relationIds = [];
            foreach ($edges as $edge) {
                $relationIds[] = $edge['id'];
                foreach ($edge['columns'] as $column) {
                    $property = substr($column['column'], 1);
                    $mappingHeader = $column['label'] ?? '-';
                    if (!array_key_exists($mappingHeader, $headers)) {
                        $errors[] = [
                            'row' => 1,
                            'column' => '?',
                            'value' => $mappingHeader,
                            'relation' => $relationName,
                            'field' => $property,
                            'ignored' => true,
                            'message' => 'Missing column.',
                        ];
                        continue;
                    }
                    $key = $headers[$mappingHeader];
                    $field = $model->$relationName->$property ?? null;
                    if ($field === null || !$field instanceof Field) {
                        $errors[] = [
                            'row' => 1,
                            'column' => $this->getColumn($key),
                            'value' => $mappingHeader,
                            'relation' => $relationName,
                            'field' => $property,
                            'ignored' => true,
                            'message' => 'Unknown field in mapping.',
                        ];
                    } elseif ($field->readonly === true) {
                        $errors[] = [
                            'row' => 1,
                            'column' => $this->getColumn($key),
                            'value' => $mappingHeader,
                            'relation' => $relationName,
                            'field' => $property,
                            'ignored' => true,
                            'message' => 'Field is readonly.',
                        ];
                    } else {
                        if (!isset($edgeMapping[$relationName])) {
                            $edgeMapping[$relationName] = [];
                        }
                        $edgeMapping[$relationName][] = [
                            'index' => $key,
                            'relatedId' => $edge['id'],
                            'edgeProperty' => $property,
                        ];
                    }
                }
            }
            $this->checkRelationExistence($relationIds, $model, $relationName, $errors);
        }
        if (count($mapping) === 0) {
            $errors[] = [
                'row' => 1,
                'column' => '?',
                'value' => '',
                'relation' => null,
                'field' => 'unknown',
                'ignored' => false,
                'message' => 'No columns found.',
            ];
        }
        return [$id, $mapping, $edgeMapping];
    }

    /**
     * @param Model $model
     * @param Row $row
     * @param int[] $mapping
     * @param array[] $edgeMapping
     * @param array[] $errors
     * @param int $rowNumber
     * @return mixed[]|null
     * @throws Error
     */
    protected function getItem(
        Model $model,
        Row $row,
        array $mapping,
        array $edgeMapping,
        array &$errors,
        int $rowNumber
    ): ?array {
        $data = [];
        $cells = $row->getCells();
        $error = false;
        foreach (get_object_vars($model) as $key => $field) {
            if ($field instanceof Field && isset($mapping[$key])) {
                $columnNumber = $mapping[$key];
                try {
                    $cell = $cells[$columnNumber];
                    if (is_object($cell)) {
                        $value = $this->convertField($field, $cell->getValue());
                    } else {
                        $value = null;
                    }
                } catch (Error $e) {
                    if ($field->null === false) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'column' => $this->getColumn($columnNumber),
                            'value' => (string)($cells[$columnNumber]->getValue() ?? ''),
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
                        'value' => (string)($cells[$columnNumber]->getValue() ?? ''),
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
                        'column' => $this->getColumn($columnNumber),
                        'value' => (string)($cells[$columnNumber]->getValue() ?? ''),
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

    protected function convertField(Field $field, mixed $value): float|int|string|null
    {
        if (empty($value)) {
            return null;
        }
        switch ($field->type) {
            case Field::DATE:
            case Field::DATE_TIME:
            case Field::TIME:
                return Date::parseToInt($value);
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
                if ($value instanceof \DateTime) {
                    // @codeCoverageIgnoreStart
                    throw new Error('Importing date columns into text fields is not supported.');
                    // @codeCoverageIgnoreEnd
                }
                return trim((string)$value);
        }
    }

    protected function getColumn(int $i): string
    {
        $columns = [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Z'
        ];
        $count = count($columns);
        $value = $columns[$i % $count];
        if ($i >= $count) {
            $value = $this->getColumn(((int)floor($i / $count)) - 1) . $value;
        }
        return $value;
    }

    /**
     * @param Relation $relation
     * @param string $property
     * @param Cell[] $cells
     * @param array[] $mapping
     * @param array[] $errors
     * @param int $rowNumber
     * @return array[]
     * @throws Error
     */
    protected function importRelation(
        Relation $relation,
        string $property,
        array $cells,
        array $mapping,
        array &$errors,
        int $rowNumber
    ): array {
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
            if ($cell === null) {
                // @codeCoverageIgnoreStart
                $value = null;
                // @codeCoverageIgnoreEnd
            } else {
                $value = $cell->getValue();
            }
            if (empty($value)) {
                $value = $field->default ?? null;
            }
            $return[$relatedId][$edgeProperty] = $this->convertField($field, $value);
        }
        return $return;
    }

    protected function checkRelationExistence(array $ids, Model $parent, string $name, array &$errors): void
    {
        $relation = $parent->$name ?? null;
        if ($relation === null || !($relation instanceof Relation)) {
            return;
        }
        $model = model($relation->name);
        $query = MysqlQueryBuilder::forModel($model, $relation->name)->tenant(JwtAuthentication::tenantId());

        $query->select(['id'])
            ->where(['column' => 'id', 'operator' => 'IN', 'value' => $ids])
            ->withTrashed();

        $databaseIds = [];
        foreach (Mysql::fetchAll($query->toSql(), $query->getParameters()) as $row) {
            $databaseIds[] = $row->id;
        }
        foreach (array_diff($ids, $databaseIds) as $missingId) {
            $errors[] = [
                'row' => 1,
                'column' => '?',
                'value' => $missingId,
                'relation' => $name,
                'field' => 'id',
                'ignored' => false,
                'message' => 'ID not found.'
            ];
        }
    }


}