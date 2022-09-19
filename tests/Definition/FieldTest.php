<?php

namespace Mrap\GraphCool\Tests\Definition;

use Carbon\Carbon;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Tests\TestCase;
use Ramsey\Uuid\Uuid;

class FieldTest extends TestCase
{

    protected $types = [
        'string' => Type::STRING,
        'id' => Type::ID,
        'bool' => Type::BOOLEAN,
        'int' => Type::INT,
        'float' => Type::FLOAT,
        'decimal' => Field::DECIMAL,
        'createdAt' => Field::CREATED_AT,
        'updatedAt' => Field::UPDATED_AT,
        'deletedAt' => Field::DELETED_AT,
        'countryCode' => Field::COUNTRY_CODE,
        'languageCode' => Field::LANGUAGE_CODE,
        'currencyCode' => Field::CURRENCY_CODE,
        'localeCode' => Field::LOCALE_CODE,
        'date' => Field::DATE,
        'dateTime' => Field::DATE_TIME,
        'time' => Field::TIME,
        'timezoneOffset' => Field::TIMEZONE_OFFSET,
    ];


    public function testConstructors(): void
    {
        foreach ($this->types as $method => $type) {
            $field = Field::$method();
            self::assertSame($type, $field->type, 'Field::' . $method . '() produced the wrong type');
        }
        $field = Field::enum(['test']);
        self::assertSame(Field::ENUM, $field->type, 'Field::enum() produced the wrong type');
    }

    public function testUnique(): void
    {
        foreach ($this->types as $method => $type) {
            /** @var Field $field */
            $field = Field::$method();
            self::assertFalse($field->unique, 'Field::' . $method . '() was unique by default');
            $field2 = $field->unique();
            self::assertTrue($field->unique, 'Field::' . $method . '()->unique() didn\'t set unique');
            self::assertFalse($field->uniqueIgnoreTrashed, 'Field::' . $method . '()->unique() didn\'t default to ignoreTrashed = false');
            self::assertSame($field, $field2, 'Field::' . $method . '()->default() does not comply to fluent interface');
        }
        $field = Field::enum(['test']);
        self::assertFalse($field->unique, 'Field::enum() was unique by default');
        $field2 = $field->unique();
        self::assertTrue($field->unique, 'Field::enum()->unique() didn\'t set unique');
        self::assertFalse($field->uniqueIgnoreTrashed, 'Field::enum()->unique() didn\'t default to ignoreTrashed = false');
        self::assertSame($field, $field2, 'Field::enum()->default() does not comply to fluent interface');

        foreach ($this->types as $method => $type) {
            /** @var Field $field */
            $field = Field::$method();
            $field2 = $field->unique(true);
            self::assertTrue($field->unique, 'Field::' . $method . '()->unique(true) didn\'t set unique');
            self::assertTrue($field->uniqueIgnoreTrashed, 'Field::' . $method . '()->unique(true) didn\'t set ignoreTrashed = true');
            self::assertSame($field, $field2, 'Field::' . $method . '()->default() does not comply to fluent interface');
        }
        $field = Field::enum(['test']);
        $field2 = $field->unique(true);
        self::assertTrue($field->unique, 'Field::enum()->unique(true) didn\'t set unique');
        self::assertTrue($field->uniqueIgnoreTrashed, 'Field::enum()->unique(true) didn\'t set ignoreTrashed = true');
        self::assertSame($field, $field2, 'Field::enum()->default() does not comply to fluent interface');
    }

    public function testNullable(): void
    {
        foreach ($this->types as $method => $type) {
            /** @var Field $field */
            $field = Field::$method();
            if (in_array($method, ['updatedAt', 'deletedAt'])) {
                self::assertTrue($field->null, 'Field::' . $method . '() should be nullable by default');
            } else {
                self::assertFalse($field->null, 'Field::' . $method . '() was nullable by default');
                $field2 = $field->nullable();
                self::assertTrue($field->null, 'Field::' . $method . '()->nullable() didn\'t set nullable');
                self::assertSame($field, $field2, 'Field::' . $method . '()->default() does not comply to fluent interface');
            }
        }
        $field = Field::enum(['test']);
        self::assertFalse($field->null, 'Field::enum() was nullable by default');
        $field2 = $field->nullable();
        self::assertTrue($field->null, 'Field::enum()->nullable() didn\'t set nullable');
        self::assertSame($field, $field2, 'Field::enum()->default() does not comply to fluent interface');
    }

    public function testDescription(): void
    {
        $description = 'test description ' . random_int(1, 9999);
        foreach ($this->types as $method => $type) {
            /** @var Field $field */
            $field = Field::$method();
            $field2 = $field->description($description);
            self::assertSame($description, $field->description, 'Field::' . $method . '()->description() didn\'t set description');
            self::assertSame($field, $field2, 'Field::' . $method . '()->default() does not comply to fluent interface');
        }
    }

    public function testDefault(): void
    {
        $date = new Carbon();

        $defaults = [
            'string' => 'some text',
            'id' => DB::id(),
            'bool' => random_int(0,1) === 1,
            'int' => random_int(0, 9999),
            'float' => random_int(0, 9999) / 100,
            'decimal' => random_int(0, 9999) / 100,
            'countryCode' => Field::COUNTRY_CODE,
            'languageCode' => Field::LANGUAGE_CODE,
            'currencyCode' => Field::CURRENCY_CODE,
            'localeCode' => Field::LOCALE_CODE,
            'date' => $date->getPreciseTimestamp(3),
            'dateTime' => $date->getPreciseTimestamp(3),
            'time' => $date->getPreciseTimestamp(3),
            'timezoneOffset' => '+02:00',
        ];
        foreach ($defaults as $method => $default) {
            /** @var Field $field */
            $field = Field::$method();
            $field2 = $field->default($default);
            self::assertSame($default, $field->default, 'Field::' . $method . '()->default() didn\'t set default');
            self::assertSame($field, $field2, 'Field::' . $method . '()->default() does not comply to fluent interface');
        }
    }

    public function testReadonly(): void
    {
        foreach ($this->types as $method => $type) {
            /** @var Field $field */
            $field = Field::$method();
            if (in_array($method, ['updatedAt', 'deletedAt', 'createdAt', 'id'])) {
                self::assertTrue($field->readonly, 'Field::' . $method . '() should be readonly by default');
            } else {
                self::assertFalse($field->readonly, 'Field::' . $method . '() was readonly by default');
                $field2 = $field->readonly();
                self::assertTrue($field->readonly, 'Field::' . $method . '()->readonly() didn\'t set readonly');
                self::assertSame($field, $field2, 'Field::' . $method . '()->readonly() does not comply to fluent interface');
            }
        }
    }

}