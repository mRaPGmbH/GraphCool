<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\WriterAbstract;
use stdClass;

class FileExport
{

    public function export(string $filename, array $data, array $columns, string $type = 'xlsx'): stdClass
    {
        $writer = $this->getWriter($type);

        $file = tempnam(sys_get_temp_dir(), 'export');
        $writer->openToFile($file);

        $headers = [];
        $i = 1;
        foreach ($columns as $column) {
            $headers[] = WriterEntityFactory::createCell($column['label'] ?? ('Column ' . $i));
            $i++;
        }
        $writer->addRow(WriterEntityFactory::createRow($headers));

        foreach ($data as $row) {
            $cells = [];

            foreach ($columns as $column) {
                $key = $column['column'];
                $cells[] = WriterEntityFactory::createCell($row->$key ?? null);
            }
            $writer->addRow(WriterEntityFactory::createRow($cells));
        }
        $writer->close();

        $result = new \stdClass();
        $result->filename = $filename;
        $result->mime_type = $this->getMimeType($type);
        $result->data_base64 = base64_encode(file_get_contents($file));
        unlink($file);
        return $result;
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
            'csv' => 'text/csv',
            default => 'application/octet-stream'
        };
    }

}