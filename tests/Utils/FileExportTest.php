<?php

namespace Mrap\GraphCool\Tests\Utils;

use Mrap\GraphCool\Utils\FileExport;
use Mrap\GraphCool\Tests\TestCase;

class FileExportTest extends TestCase
{

    //protected $csv = '77u/RmFtaWxpZW5uYW1lLGlkLGRhdGUsZGF0ZV90aW1lLHRpbWUKdGVzdCwxMjMsMTk3MC0wMS0wMSwxOTcwLTAxLTAxVDAwOjAwOjAyKzAwOjAwLDAwOjAwOjAwKzAwOjAwCiwzNDUsLCwK';
    protected $csv = '77u/RmFtaWxpZW5uYW1lLGlkLGRhdGUsZGF0ZV90aW1lLHRpbWUKdGVzdCwxMjMsMTk3MC0wMS0wMSwxOTcwLTAxLTAxVDAwOjAwOjAyWiwwMDowMDowMFoKLDM0NSwsLAo=';
    protected $csvExcel = '77u/RmFtaWxpZW5uYW1lO2lkO2RhdGU7ZGF0ZV90aW1lO3RpbWUKdGVzdDsxMjM7MDEuMDEuMTk3MDsiMDEuMDEuMTk3MCAwMDowMCI7MDA6MDAKOzM0NTs7Owo=';
    //protected $xlsx = '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';
    //protected $ods = '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';
    protected $columns = [
        ['column' => 'last_name','label' => 'Familienname'],
        ['column' => 'id','label' => 'id'],
        ['column' => 'date','label' => 'date'],
        ['column' => 'date_time','label' => 'date_time'],
        ['column' => 'time','label' => 'time'],
    ];
    protected $data;

    public function setUp(): void
    {
        $this->data = [
            (object) ['last_name' => 'test','id' => '123','date' => '2020-01-01', 'date_time' => '2020-01-01T12:12:00.000Z', 'time' => '12:12:00.000Z'],
            (object) ['last_name' => null,'id' => '345']
        ];
    }

    public function testBasicCsv(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $export = new FileExport();
        $result = $export->export('DummyModel', $this->data, ['columns' => $this->columns], 'csv');
        self::assertSame($this->csv, $result->data_base64, 'Generated csv does not match provided data.');
        self::assertStringEndsWith('.csv', $result->filename, 'Filename does not match.');
        self::assertSame('text/csv', $result->mime_type, 'Unexpected mime type.');
    }

    public function testBasicCsvExcel(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $export = new FileExport();
        $result = $export->export('DummyModel', $this->data, ['columns' => $this->columns], 'csv_excel');
        self::assertSame($this->csvExcel, $result->data_base64, 'Generated csv does not match provided data.');
        self::assertStringEndsWith('.csv', $result->filename, 'Filename does not match.');
        self::assertSame('text/csv', $result->mime_type, 'Unexpected mime type.');
    }

    public function testBasicXlsx(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $export = new FileExport();
        $result = $export->export('DummyModel', $this->data, ['columns' => $this->columns], 'xlsx');
        //self::assertSame($this->xlsx, $result->data_base64, 'Generated xlsx does not match provided data.'); // file is non-deterministic (timestamps?)
        self::assertStringEndsWith('.xlsx', $result->filename, 'Filename does not match.');
        self::assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $result->mime_type, 'Unexpected mime type.');
    }

    public function testBasicOds(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $export = new FileExport();
        $result = $export->export('DummyModel', $this->data, ['columns' => $this->columns], 'ods');
        //self::assertSame($this->ods, $result->data_base64, 'Generated ods does not match provided data.'); // file is non-deterministic (timestamps?)
        self::assertStringEndsWith('.ods', $result->filename, 'Filename does not match.');
        self::assertSame('application/vnd.oasis.opendocument.spreadsheet', $result->mime_type, 'Unexpected mime type.');
    }

    public function testRelations(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $columns = [
            ['column' => 'id','label' => 'id'],
        ];
        $belongsTo = [
            ['column' => 'id','label' => 'parent_id'],
            ['column' => 'last_name','label' => 'parent_name'],
        ];
        $belongsToMany = [[
            'id' => 125,
            'columns' => [
                ['column' => 'id','label' => 'many_id'],
                ['column' => 'last_name','label' => 'many_name'],
                ['column' => '_pivot_property','label' => 'pivot_property'],
            ]
        ]];

        $csv = '77u/aWQscGFyZW50X2lkLHBhcmVudF9uYW1lLG1hbnlfaWQsbWFueV9uYW1lLHBpdm90X3Byb3BlcnR5CjEyMywxMjQseHgsMTI1LHl5LCJwaXZvdCBkYXRhIgo0NTYsNDU3LGFhLCwsCg==';
        $data = [
            (object) [
                'id' => '123',
                'belongs_to' => function(array $args) { return (object) ['id' => 124, 'last_name' => 'xx'];},
                'belongs_to_many' => function(array $args) {
                    return [
                        'edges' => [
                            (object) [
                                'parent_id' => 125,
                                'pivot_property' => 'pivot data',
                                '_node' => (object) ['id' => 125, 'last_name' => 'yy']
                            ]
                        ]
                    ];
                }
            ],
            (object) [
                'id' => '456',
                'belongs_to' => function(array $args) { return (object) ['id' => 457, 'last_name' => 'aa'];},
                'belongs_to_many' => function(array $args) {
                    return [
                        'edges' => []
                    ];
                }
            ],
        ];

        $export = new FileExport();
        $result = $export->export('DummyModel', $data, ['columns' => $columns, 'belongs_to' => $belongsTo, 'belongs_to_many' => $belongsToMany], 'csv');
        self::assertSame($csv, $result->data_base64, 'Generated csv does not match provided data.');
    }

}