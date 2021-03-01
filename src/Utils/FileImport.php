<?php


namespace Mrap\GraphCool\Utils;


use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\ReaderAbstract;
use Box\Spout\Reader\SheetInterface;

class FileImport
{

    public function import(string $data, array $columns): array
    {
        $file = tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($file, base64_decode($data));

        $reader = $this->getReader(mime_content_type($file));
        if ($reader === null) {
            throw new \Exception('Could not import: Unknown MimeType: '. mime_content_type($file));
        }
        $reader->open($file);
        $result = [];

        /** @var SheetInterface $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            $firstRow = true;
            $mapping = [];
            /** @var Row $row */
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                if ($firstRow) {
                    foreach ($cells as $key => $cell) {
                        $header = $cell->getValue();
                        foreach ($columns as $column) {
                            if ($column['label'] === $header) {
                                $mapping[$key] = $column['column'];
                            }
                        }
                    }
                    $firstRow = false;
                } else {
                    $item = [];
                    foreach ($cells as $key => $cell) {
                        $property = $mapping[$key];
                        $item[$property] = $cell->getValue();
                    }
                    $result[] = $item;
                }
            }
            break; // only the first sheet will be used
        }
        $reader->close();
        unlink($file);
        return $result;
    }

    protected function getReader(string $mimeType): ?ReaderAbstract
    {
        return match ($mimeType) {
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ReaderEntityFactory::createXLSXReader(),
            'application/vnd.oasis.opendocument.spreadsheet' => ReaderEntityFactory::createODSReader(),
            'text/csv', 'text/plain', 'application/csv' => ReaderEntityFactory::createCSVReader(),
            default => null
        };
    }

}