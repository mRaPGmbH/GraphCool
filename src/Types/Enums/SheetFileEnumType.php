<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;

class SheetFileEnumType extends EnumType
{

    public function __construct()
    {
        $config = [
            'name' => '_ExportFile',
            'description' => 'The format and file type that should be exported.',
            'values' => [
                'XLSX' => ['value' => 'xlsx', 'description' => 'Microsoft Excel Spreadsheet'],
                'ODS' => ['value' => 'ods', 'description' => 'Open Document Spreadsheet'],
                'CSV' => ['value' => 'csv', 'description' => 'Comma Separated Values'],
                'CSV_EXCEL' => ['value' => 'csv_excel', 'description' => 'CSV using semicolon instead of comma'],
            ]
        ];
        parent::__construct($config);
    }
}
