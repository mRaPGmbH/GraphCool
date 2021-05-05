<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Utils;


use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Reader as CSVReader;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use GraphQL\Error\Error;
use JsonException;

class FileImport
{

    public function import(?string $data, array $columns, int $index): array
    {
        if ($data === null) {
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
            file_put_contents($file, base64_decode($data));
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
                        if (isset($mapping[$key])) {
                            $property = $mapping[$key];
                            $item[$property] = $cell->getValue();
                            if ($property === 'id' && empty($item[$property])) {
                                unset($item[$property]);
                            }
                        }
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