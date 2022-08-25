<?php

namespace Mrap\GraphCool\Tests\Utils;

use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\FileImport2;

class FileImport2Test extends TestCase
{
    public function tearDown(): void
    {
        unset($_FILES[0]);
        unset($_REQUEST['map']);
        parent::tearDown();
    }

    public function testImportInsert(): void
    {
        $data = [
            ['Familienname'],
            ['Huber']
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['last_name' => 'Huber']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportUpdate(): void
    {
        $data = [
            ['id','Familienname'],
            ['123','Huber']
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname'],
            ['column' => 'id']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([], $inserts, 'There should be nothing to insert.');
        self::assertSame([2 => ['id' => '123','data' => ['last_name' => 'Huber']]], $updates, 'Updates don\'t match expectation.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportNotEnoughRowsError(): void
    {
        $data = [
            ['id','Familienname'],
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname'],
            ['column' => 'id']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([], $inserts, 'There should be nothing to insert.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame('Sheet must contain at least one row with headers, and one row with data.', $errors[0]['message']);
    }

    public function testImportDate(): void
    {
        $data = [
            ['Datum'],
            ['2020-01-01']
        ];
        $columns = [
            ['column' => 'date', 'label' => 'Datum']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['date' => 1577836800000]], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportDateTime(): void
    {
        $data = [
            ['Termin'],
            ['2020-01-01 01:30:00']
        ];
        $columns = [
            ['column' => 'date_time', 'label' => 'Termin']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['date_time' => 1577842200000]], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportTime(): void
    {
        $data = [
            ['Zeit'],
            ['01:30:00']
        ];
        $columns = [
            ['column' => 'time', 'label' => 'Zeit']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['time' => strtotime(date('Y-m-d').' 01:30:00')*1000]], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportFloat(): void
    {
        $data = [
            ['Zahl'],
            ['0.123']
        ];
        $columns = [
            ['column' => 'float', 'label' => 'Zahl']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['float' => 0.123]], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportCountry(): void
    {
        $data = [
            ['Land'],
            ['Österreich']
        ];
        $columns = [
            ['column' => 'country', 'label' => 'Land']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['country' => 'AT']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportTimezone(): void
    {
        $data = [
            ['Zeitzone'],
            ['+02:00']
        ];
        $columns = [
            ['column' => 'timezone', 'label' => 'Zeitzone']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['timezone' => 7200]], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportLocale(): void
    {
        $data = [
            ['Sprache'],
            ['de_AT']
        ];
        $columns = [
            ['column' => 'locale', 'label' => 'Sprache']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['locale' => 'de_AT']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportCurrency(): void
    {
        $data = [
            ['Währung'],
            ['EUR']
        ];
        $columns = [
            ['column' => 'currency', 'label' => 'Währung']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['currency' => 'EUR']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportLanguage(): void
    {
        $data = [
            ['Sprache'],
            ['de']
        ];
        $columns = [
            ['column' => 'language', 'label' => 'Sprache']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['language' => 'de']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportEnum(): void
    {
        $data = [
            ['Auswahl'],
            ['A']
        ];
        $columns = [
            ['column' => 'enum', 'label' => 'Auswahl']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['enum' => 'A']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportEnumError(): void
    {
        $data = [
            ['Auswahl'],
            ['not-an-enum-value']
        ];
        $columns = [
            ['column' => 'enum', 'label' => 'Auswahl']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([], $inserts, 'There should be nothing to insert.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([[
            'row' => 2,
            'column' => 'A',
            'value' => 'not-an-enum-value',
            'relation' => null,
            'field' => 'enum',
            'ignored' => false,
            'message' => 'Invalid value: not-an-enum-value',
        ]], $errors, 'Errors don\'t match expectation.');
    }

    public function testImportNull(): void
    {
        $data = [
            ['id','Familienname'],
            ['123','']
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname'],
            ['column' => 'id']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([], $inserts, 'There should be nothing to insert.');
        self::assertSame([2 => ['id'=>'123', 'data'=>['last_name' => null]]], $updates, 'Inserts don\'t match expectation.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportNullError(): void
    {
        $data = [
            ['id','Auswahl'],
            ['123','']
        ];
        $columns = [
            ['column' => 'enum', 'label' => 'Auswahl'],
            ['column' => 'id']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([], $inserts, 'There should be nothing to insert.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([[
            'row' => 2,
            'column' => 'B',
            'value' => '',
            'relation' => null,
            'field' => 'enum',
            'ignored' => false,
            'message' => 'Mandatory field may not be empty',
        ]], $errors, 'Errors don\'t match expectation.');
    }
    public function testImportColumns(): void
    {
        $data = [
            ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Z','this-should-be-AA'],
            ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Z','']
        ];
        $columns = [
            ['column' => 'enum', 'label' => 'this-should-be-AA'],
            ['column' => 'id', 'label' => 'A']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([], $inserts, 'There should be nothing to insert.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([[
            'row' => 2,
            'column' => 'AA',
            'value' => '',
            'relation' => null,
            'field' => 'enum',
            'ignored' => false,
            'message' => 'Mandatory field may not be empty',
        ]], $errors, 'Errors don\'t match expectation.');
    }

    public function testImportDateError(): void
    {
        $data = [
            ['Datum'],
            ['not-a-date']
        ];
        $columns = [
            ['column' => 'date', 'label' => 'Datum']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([[
            'row' => 2,
            'column' => 'A',
            'value' => 'not-a-date',
            'relation' => null,
            'field' => 'date',
            'ignored' => true,
            'message' => 'Could not parse date value: not-a-date',
        ]], $errors, 'There should be a date parse error.');
    }

    public function testImportRelation(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id' => '123-test-id']]);
        Mysql::setConnector($mock);

        $data = [
            ['Familienname','Pivot Property'],
            ['Huber','test A']
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname']
        ];
        $edgeColumns = ['belongs_to_many' => [['id' => '123-test-id', 'columns' => [['column' => '_pivot_property', 'label' => 'Pivot Property']]]]];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns, $edgeColumns);
        self::assertSame([
            2 => ['last_name' => 'Huber', 'belongs_to_many' => ['123-test-id'=>['where'=>['column'=>'id','operator'=>'=','value'=>'123-test-id'],'pivot_property'=>'test A']]]
        ], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportRelationEmpty(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id' => '123-test-id']]);
        Mysql::setConnector($mock);

        $data = [
            ['Familienname','Pivot Property'],
            ['Huber','']
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname']
        ];
        $edgeColumns = ['belongs_to_many' => [['id' => '123-test-id', 'columns' => [['column' => '_pivot_property', 'label' => 'Pivot Property']]]]];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns, $edgeColumns);
        self::assertSame([
            2 => ['last_name' => 'Huber', 'belongs_to_many' => ['123-test-id'=>['where'=>['column'=>'id','operator'=>'=','value'=>'123-test-id'],'pivot_property'=>null]]]
        ], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportExcelCsv(): void
    {
        $data = [
            ['id','Familienname'],
            [null,'Huber']
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns, [], true);
        self::assertSame([2 => ['last_name' => 'Huber']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportReadonly(): void
    {
        $data = [
            ['Familienname', 'ignoreMe'],
            ['Huber','no import']
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname'],
            ['column' => 'ignoreMe']
        ];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns);
        self::assertSame([2 => ['last_name' => 'Huber']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame('Unknown field in mapping.', $errors[0]['message']);
        self::assertSame('ignoreMe', $errors[0]['value']);
        self::assertTrue($errors[0]['ignored']);
    }

    public function testImportNothingError(): void
    {
        $this->expectException(Error::class);
        $import = new FileImport2();
        $import->import('DummyModel', ['columns'=>[]], 0);
    }

    public function testImportMapError(): void
    {
        $this->expectException(Error::class);
        $_REQUEST['map'] = '[not-a-valid-json';
        $import = new FileImport2();
        $import->import('DummyModel', ['columns'=>[]], 0);
    }

    public function testImportMapError2(): void
    {
        $this->expectException(Error::class);
        $_REQUEST['map'] = '[["no-match"]]';
        $import = new FileImport2();
        $import->import('DummyModel', ['columns'=>[]], 0);
    }

    public function testImportFileError(): void
    {
        $this->expectException(Error::class);
        $_REQUEST['map'] = '[["0.variables.file"]]';
        $import = new FileImport2();
        $import->import('DummyModel', ['columns'=>[]], 0);
    }

    public function testImportFileExcel(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import');
        copy($this->dataPath().'/import.xlsx', $file);

        $import = new FileImport2();
        [$inserts, $updates, $errors] = $import->import('DummyModel', [
            'columns' => [['column' => 'last_name', 'label' => 'Familienname']],
            'file' => ['tmp_name' => $file]
        ]);
        self::assertSame([2 => ['last_name' => 'Huber']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
        self::assertFalse(file_exists($file), 'Temporary file should get deleted');
    }

    public function testImportFileExcelError(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import');
        copy($this->dataPath().'/import2.xlsx', $file);

        $import = new FileImport2();
        [$inserts, $updates, $errors] = $import->import('DummyModel', [
            'columns' => [['column' => 'last_name', 'label' => 'Familienname']],
            'file' => ['tmp_name' => $file]
        ]);
        self::assertSame([], $inserts, 'There should be nothing to insert.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame('File contains multiple sheets, but only one sheet can be imported.', $errors[0]['message']);
        self::assertFalse($errors[0]['ignored']);
    }

    public function testImportFileUnknownError(): void
    {
        $this->expectException(Error::class);
        $file = tempnam(sys_get_temp_dir(), 'import');
        copy($this->dataPath().'/test.pdf', $file);

        $import = new FileImport2();
        $import->import('DummyModel', [
            'columns' => [['column' => 'last_name', 'label' => 'Familienname']],
            'file' => ['tmp_name' => $file]
        ]);
    }

    public function testImportFileOds(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import');
        copy($this->dataPath().'/import.ods', $file);

        $import = new FileImport2();
        [$inserts, $updates, $errors] = $import->import('DummyModel', [
            'columns' => [['column' => 'last_name', 'label' => 'Familienname']],
            'file' => ['tmp_name' => $file]
        ]);

        self::assertSame([2 => ['last_name' => 'Huber']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
        self::assertFalse(file_exists($file), 'Temporary file should get deleted');
    }

    public function testBase64Error(): void
    {
        $this->expectException(Error::class);
        $import = new FileImport2();
        $import->import('DummyModel', [
            'columns' => [['column' => 'last_name', 'label' => 'Familienname']],
            'file' => 'not-valid-base64-data'
        ]);
    }

    protected function getImportResult(array $data, array $columns, array $edgeColumns = [], bool $excelCsv = false): array
    {
        if ($excelCsv === true) {
            $base64 = $this->prepare($data, ';', '"');
        } else {
            $base64 = $this->prepare($data);
        }
        $import = new FileImport2();
        $args = [
            'columns' => $columns,
            'data_base64' => $base64,
        ];
        $args = array_merge($args, $edgeColumns);
        return $import->import('DummyModel', $args, 0);
    }

    protected function prepare(array $data, $separator = ',', $enclosure = '\''): string
    {
        $rows = [];
        foreach ($data as $row) {
            $cells = [];
            foreach ($row as $cell) {
                $slashedCell = addslashes($cell);
                if (strpos($cell, $separator) !== false || $slashedCell !== $cell) {
                    $cell = $enclosure . $slashedCell . $enclosure;
                }
                $cells[] = $cell;
            }
            $rows[] = implode($separator, $cells);
        }
        return base64_encode(implode("\n", $rows));
    }

}