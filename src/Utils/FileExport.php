<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Style\Style;
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

    protected string $type;

    public function export(string $name, array $data, array $args, string $type = 'xlsx'): stdClass
    {
        $classname = 'App\\Models\\' . $name;
        $model = new $classname();

        $this->type = $type;

        $writer = $this->getWriter($type);
        $result = new \stdClass();
        if ($type === 'csv_excel') {
            /** @var Writer $writer */
            $result->filename = $name . '-Export_' . date('Y-m-d') . '.csv';
            $writer->setFieldDelimiter(';');
            $writer->setFieldEnclosure('"');
        } else {
            $result->filename = $name . '-Export_' . date('Y-m-d') . '.' . $type;
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


    protected Style $excelDateStyle;
    protected Style $excelDateTimeStyle;
    protected Style $excelTimeStyle;

    protected function getCell(Field $field, mixed $value): Cell
    {
        if ($value === null) {
            return WriterEntityFactory::createCell('');
        }
        switch ($field->type) {
            case Field::DATE:
                $carbon = Date::getObject($value);
                if ($this->type === 'xlsx') {
                    return WriterEntityFactory::createCell($carbon->getTimestamp() / 86400 + 25569, $this->excelDateStyle);
                }
                if ($this->type === 'ods' || $this->type === 'csv_excel') {
                    $value = $carbon->format('d.m.Y');
                } else {
                    $value = $carbon->format('Y-m-d');
                }
                return WriterEntityFactory::createCell($value);
            case Field::DATE_TIME:
            case Field::UPDATED_AT:
            case Field::CREATED_AT:
            case Field::DELETED_AT:
                $carbon = DateTime::getObject($value);
                if ($this->type === 'xlsx') {
                    return WriterEntityFactory::createCell($carbon->getTimestamp() / 86400 + 25569, $this->excelDateTimeStyle);
                }
                if ($this->type === 'ods' || $this->type === 'csv_excel') {
                    $value = $carbon->format('d.m.Y H:i');
                } else {
                    $value = $carbon->format('Y-m-d\TH:i:sp');
                }
                return WriterEntityFactory::createCell($value);
            case Field::TIME:
                $carbon = DateTime::getObject($value);
                if ($this->type === 'xlsx') {
                    return WriterEntityFactory::createCell($carbon->getTimestamp() / 86400 + 25569, $this->excelTimeStyle);
                }
                if ($this->type === 'ods' || $this->type === 'csv_excel') {
                    $value = $carbon->format('H:i');
                } else {
                    $value = $carbon->format('H:i:sp');
                }
                return WriterEntityFactory::createCell($value);
            default:
                return WriterEntityFactory::createCell($value);
        }
    }


    protected function getRowCells(Model $model, array $args, stdClass $row): array
    {
        $cells = [];
        $this->excelDateStyle = (new StyleBuilder())->setFormat('dd/mm/yyyy')->build();
        $this->excelDateTimeStyle = (new StyleBuilder())->setFormat('dd/mm/yyyy hh:mm')->build();
        $this->excelTimeStyle =  (new StyleBuilder())->setFormat('hh:mm')->build();

        foreach ($args['columns'] as $column) {
            $key = $column['column'];
            $cells[] = $this->getCell($model->$key, $row->$key ?? null);
        }
        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if (($relation->type === Relation::BELONGS_TO || $relation->type === Relation::HAS_ONE) && isset($args[$key])) {
                $closure = $row->$key;
                $data = $closure([]);
                foreach ($args[$key] as $column) {
                    $p = $column['column'];
                    $value = $data->$p ?? null;
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