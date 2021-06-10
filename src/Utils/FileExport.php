<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\CSV\Writer;
use Box\Spout\Writer\WriterAbstract;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\Scalars\Date;
use Mrap\GraphCool\Types\Scalars\DateTime;
use Mrap\GraphCool\Types\Scalars\Time;
use stdClass;

class FileExport
{

    public function export(string $name, array $data, array $args, string $type = 'xlsx'): stdClass
    {
        $classname = 'App\\Models\\' . $name;
        $model = new $classname();

        $writer = $this->getWriter($type);
        $result = new \stdClass();
        if ($type === 'csv_excel') {
            /** @var Writer $writer */
            $result->filename = $name . '-Export_'.date('Y-m-d_H-i-s').'.csv';
            $writer->setFieldDelimiter(';');
            $writer->setFieldEnclosure('"');
        } else {
            $result->filename = $name . '-Export_'.date('Y-m-d_H-i-s').'.'.$type;
        }

        $file = tempnam(sys_get_temp_dir(), 'export');
        $writer->openToFile($file);
        $writer->addRow(WriterEntityFactory::createRow($this->getHeaders($model, $args)));
        foreach ($data as $row) {
            $writer->addRow(WriterEntityFactory::createRow($this->getRowCells($model, $args, $row)));
        }
        $writer->close();

        $result->mime_type = $this->getMimeType($type);
        $result->data_base64 = base64_encode(file_get_contents($file));
        unlink($file);
        return $result;
    }

    protected function getHeaders(Model $model, array $args): array
    {
        $headers = [];
        $i = 1;
        foreach ($args['columns'] as $column) {
            $headers[] = WriterEntityFactory::createCell($column['label'] ?? ('Column ' . $i++));
        }
        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if (($relation->type === Relation::BELONGS_TO || $relation->type === Relation::HAS_ONE) && isset($args[$key])) {
                foreach ($args[$key] as $column) {
                    $headers[] = WriterEntityFactory::createCell($column['label'] ?? ('Column ' . $i++));
                }
            }
            if ($relation->type === Relation::BELONGS_TO_MANY && isset($args[$key])) {
                foreach ($args[$key] as $related) {
                    foreach ($related['columns'] as $column) {
                        $headers[] = WriterEntityFactory::createCell($column['label'] ?? ('Column ' . $i++));
                    }
                }
            }
        }
        return $headers;
    }

    protected function getExcelFormat(string $type)
    {
        return match ($type) {
            'date' => 'TT.MM.JJJJ',
            'dateTime' => 'TT.MM.JJJJ hh:mm',
            'time' => 'hh:mm'
        };
    }

    protected function getFormat(string $type)
    {
        return match ($type) {
            Field::DATE => 'd.m.Y',
            Field::DATE_TIME => 'd.m.Y H:i',
            Field::TIME => 'H:i'
        };
    }


    protected function getRowCells(Model $model, array $args, stdClass $row): array
    {
        $cells = [];
        $dateStyle = (new StyleBuilder())->setFormat($this->getExcelFormat('date'))->build();
        $dateTimeStyle = (new StyleBuilder())->setFormat($this->getExcelFormat('dateTime'))->build();
        $timeStyle =  (new StyleBuilder())->setFormat($this->getExcelFormat('time'))->build();

        foreach ($args['columns'] as $column) {
            $key = $column['column'];
            $value = $this->convertField($model->$key, $row->$key ?? null);
            //var_dump($value);
            $cells[] = match ($model->$key->type) {
                Field::DATE => WriterEntityFactory::createCell($value, $dateStyle),
                Field::DATE_TIME, Field::UPDATED_AT, Field::CREATED_AT, Field::DELETED_AT => WriterEntityFactory::createCell($value, $dateTimeStyle),
                Field::TIME => WriterEntityFactory::createCell($value, $timeStyle),
                default => WriterEntityFactory::createCell($value)
            };
        }
        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if (($relation->type === Relation::BELONGS_TO || $relation->type === Relation::HAS_ONE) && isset($args[$key])) {
                foreach ($args[$key] as $column) {
                    $closure = $row->$key;
                    $data = $closure();

                    $value = print_r($data, true);
                    $cells[] = WriterEntityFactory::createCell($value);
                }
            }
            if ($relation->type === Relation::BELONGS_TO_MANY && isset($args[$key])) {
                foreach ($args[$key] as $related) {
                    $closure = $row->$key;
                    $data = $closure(['where' => [['column' => 'id', 'operator' => '=', 'value' => $related['id']]]]);
                    foreach ($related['columns'] as $column) {
                        if (count($data['edges']) === 0) {
                            $value = null;
                        } elseif (str_starts_with($column['column'], '_')) {
                            $property = substr($column['column'], 1);
                            $value = $data['edges'][0]->$property ?? null;
                        } else {
                            $property = $column['column'];
                            $value = $data['edges'][0]->_node->$property ?? null;
                        }
                        $cells[] = WriterEntityFactory::createCell($value);
                    }
                }
            }
        }
        return $cells;
    }

    protected function convertField(Field $field, $value): float|int|string|null|\DateTime
    {
        if ($field->null === true && $value === null) {
            return null;
        }
        switch ($field->type) {
            case Field::DATE:
                return Date::getObject($value)->format($this->getFormat(Field::DATE));
            case Field::DATE_TIME:
            case Field::UPDATED_AT:
            case Field::CREATED_AT:
            case Field::DELETED_AT:
                return DateTime::getObject($value)->format(Field::DATE_TIME);
            case Field::TIME:
                return Time::getObject($value)->format(Field::TIME);
            case Field::DECIMAL:
                return (float) $value;
            default:
                return (string) $value;
        }
    }

    protected function getWriter(string $type): WriterAbstract
    {
        return match ($type) {
            'xlsx' => WriterEntityFactory::createXLSXWriter(),
            'ods' => WriterEntityFactory::createODSWriter(),
            default => WriterEntityFactory::createCSVWriter(),
        };
    }

    protected function getMimeType(string $type): string
    {
        return match ($type) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'csv', 'csv_excel' => 'text/csv',
            default => 'application/octet-stream'
        };
    }

}