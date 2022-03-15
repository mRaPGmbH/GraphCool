<?php

namespace Mrap\GraphCool\Tests\Utils;

use GraphQL\Error\Error;
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
        self::assertSame([['last_name' => 'Huber']], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['id' => '123','data' => ['last_name' => 'Huber']]], $updates, 'Updates don\'t match expectation.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportNotEnoughRowsError(): void
    {
        $this->expectException(Error::class);

        $data = [
            ['id','Familienname'],
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname'],
            ['column' => 'id']
        ];
        $this->getImportResult($data, $columns);
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
        self::assertSame([['date' => 1577836800000]], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['date_time' => 1577842200000]], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['time' => strtotime(date('Y-m-d').' 01:30:00')*1000]], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['float' => 0.123]], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['country' => 'AT']], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['timezone' => 7200]], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['locale' => 'de_AT']], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['currency' => 'EUR']], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['language' => 'de']], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['enum' => 'A']], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['id'=>'123', 'data'=>['last_name' => null]]], $updates, 'Inserts don\'t match expectation.');
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
        $data = [
            ['Familienname','Pivot Property'],
            ['Huber','test A']
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname']
        ];
        $edgeColumns = ['belongs_to_many' => [['id' => '123-test-id', 'columns' => [['column' => '_pivot_property', 'label' => 'Pivot Property'],['column' => '_none', '-']]]]];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns, $edgeColumns);
        self::assertSame([
            ['last_name' => 'Huber', 'belongs_to_many' => ['123-test-id'=>['where'=>['column'=>'id','operator'=>'=','value'=>'123-test-id'],'pivot_property'=>'test A']]]
        ], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
    }

    public function testImportRelationEmpty(): void
    {
        $data = [
            ['Familienname','Pivot Property'],
            ['Huber','']
        ];
        $columns = [
            ['column' => 'last_name', 'label' => 'Familienname']
        ];
        $edgeColumns = ['belongs_to_many' => [['id' => '123-test-id', 'columns' => [['column' => '_pivot_property', 'label' => 'Pivot Property'],['column' => '_none', '-']]]]];
        [$inserts, $updates, $errors] = $this->getImportResult($data, $columns, $edgeColumns);
        self::assertSame([
            ['last_name' => 'Huber', 'belongs_to_many' => ['123-test-id'=>['where'=>['column'=>'id','operator'=>'=','value'=>'123-test-id'],'pivot_property'=>null]]]
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
        self::assertSame([['last_name' => 'Huber']], $inserts, 'Inserts don\'t match expectation.');
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
        self::assertSame([['last_name' => 'Huber']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
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

    public function xtestImportFileExcel(): void
    {
        $_REQUEST['map'] = '[["0.variables.file"]]';
        $file = tempnam(sys_get_temp_dir(), 'import');
        copy($this->dataPath().'/import.xlsx', $file);
        $_FILES[0] = ['tmp_name' => $file];
        $import = new FileImport2();
        [$inserts, $updates, $errors] = $import->import('DummyModel', ['columns'=>[['column' => 'last_name', 'label' => 'Familienname']]], 0);
        self::assertSame([['last_name' => 'Huber']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
        self::assertFalse(file_exists($file), 'Temporary file should get deleted');
    }

    public function testImportFileExcelError(): void
    {
        $this->expectException(Error::class);
        $_REQUEST['map'] = '[["0.variables.file"]]';
        $file = tempnam(sys_get_temp_dir(), 'import');
        copy($this->dataPath().'/import2.xlsx', $file);
        $_FILES[0] = ['tmp_name' => $file];
        $import = new FileImport2();
        $import->import('DummyModel', ['columns'=>[['column' => 'last_name', 'label' => 'Familienname']]], 0);
    }

    public function testImportFileUnknownError(): void
    {
        $this->expectException(Error::class);
        $_REQUEST['map'] = '[["0.variables.file"]]';
        $file = tempnam(sys_get_temp_dir(), 'import');
        copy($this->dataPath().'/test.pdf', $file);
        $_FILES[0] = ['tmp_name' => $file];
        $import = new FileImport2();
        $import->import('DummyModel', ['columns'=>[['column' => 'last_name', 'label' => 'Familienname']]], 0);
    }

    public function xtestImportFileOds(): void
    {
        $_REQUEST['map'] = '[["0.variables.file"]]';
        $file = tempnam(sys_get_temp_dir(), 'import');
        copy($this->dataPath().'/import.ods', $file);
        $_FILES[0] = ['tmp_name' => $file];
        $import = new FileImport2();
        [$inserts, $updates, $errors] = $import->import('DummyModel', ['columns'=>[['column' => 'last_name', 'label' => 'Familienname']]], 0);
        self::assertSame([['last_name' => 'Huber']], $inserts, 'Inserts don\'t match expectation.');
        self::assertSame([], $updates, 'There should be nothing to update.');
        self::assertSame([], $errors, 'There should be no errors.');
        self::assertFalse(file_exists($file), 'Temporary file should get deleted');
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