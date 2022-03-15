<?php


namespace Mrap\GraphCool\Tests\Utils;


use Carbon\Carbon;
use Mrap\GraphCool\Utils\Date;
use Mrap\GraphCool\Utils\TimeZone;
use Mrap\GraphCool\Tests\TestCase;

class DateTest extends TestCase
{
    public function testDateDays(): void
    {
        $expected = '2021-06-08';
        $dates = [
            '08.06.2021', '8.6.2021', '08.6.2021', '8.06.2021',
            '08. 06. 2021', '8. 6. 2021', '08. 6. 2021', '8. 06. 2021',
            '08.06.21', '8.6.21', '08.6.21', '8.06.21',
            '08. 06. 21', '8. 6. 21', '08. 6. 21', '8. 06. 21',
            '08/06/2021', '8/6/2021', '08/6/2021', '8/06/2021',
            '08/06/21', '8/6/21', '08/6/21', '8/06/21',
            strtotime($expected)
        ];

        foreach ($dates as $input) {
            $carbon = Date::parse($input);
            $actual = $carbon->format('Y-m-d');
            self::assertSame($expected, $actual, 'Failed to correctly parse: ' . $input . ' - result: ' . $actual);
        }
    }

    public function testDateMinutes(): void
    {
        $expected = '2021-06-08 09:54';
        $dates = [
            '08.06.2021 09:54', '8.6.2021 09:54', '08.6.2021 09:54', '8.06.2021 09:54',
            '08. 06. 2021 09:54', '8. 6. 2021 09:54', '08. 6. 2021 09:54', '8. 06. 2021 09:54',
            '08.06.21 09:54', '8.6.21 09:54', '08.6.21 09:54', '8.06.21 09:54',
            '08. 06. 21 09:54', '8. 6. 21 09:54', '08. 6. 21 09:54', '8. 06. 21 09:54',
            '08/06/2021 09:54', '8/6/2021 09:54', '08/6/2021 09:54', '8/06/2021 09:54',
            '08/06/21 09:54', '8/6/21 09:54', '08/6/21 09:54', '8/06/21 09:54',

            '08.06.2021 9:54', '8.6.2021 9:54', '08.6.2021 9:54', '8.06.2021 9:54',
            '08. 06. 2021 9:54', '8. 6. 2021 9:54', '08. 6. 2021 9:54', '8. 06. 2021 9:54',
            '08.06.21 9:54', '8.6.21 9:54', '08.6.21 9:54', '8.06.21 9:54',
            '08. 06. 21 9:54', '8. 6. 21 9:54', '08. 6. 21 9:54', '8. 06. 21 9:54',
            '08/06/2021 9:54', '8/6/2021 9:54', '08/6/2021 9:54', '8/06/2021 9:54',
            '08/06/21 9:54', '8/6/21 9:54', '08/6/21 9:54', '8/06/21 9:54',

            '08/06/2021 0954', '8/6/2021 0954', '08/6/2021 0954', '8/06/2021 0954',
            '08/06/21 0954', '8/6/21 0954', '08/6/21 0954', '8/06/21 0954',
            strtotime($expected)
        ];

        foreach ($dates as $input) {
            $carbon = Date::parse($input);
            $actual = $carbon->format('Y-m-d H:i');
            self::assertSame($expected, $actual, 'Failed to correctly parse: ' . $input . ' - result: ' . $actual);
        }
    }

    public function testDateSeconds(): void
    {
        $expected = '2021-06-08 09:54:12';
        $dates = [
            '08.06.2021 09:54:12', '8.6.2021 09:54:12', '08.6.2021 09:54:12', '8.06.2021 09:54:12',
            '08. 06. 2021 09:54:12', '8. 6. 2021 09:54:12', '08. 6. 2021 09:54:12', '8. 06. 2021 09:54:12',
            '08.06.21 09:54:12', '8.6.21 09:54:12', '08.6.21 09:54:12', '8.06.21 09:54:12',
            '08. 06. 21 09:54:12', '8. 6. 21 09:54:12', '08. 6. 21 09:54:12', '8. 06. 21 09:54:12',
            '08/06/2021 09:54:12', '8/6/2021 09:54:12', '08/6/2021 09:54:12', '8/06/2021 09:54:12',
            '08/06/21 09:54:12', '8/6/21 09:54:12', '08/6/21 09:54:12', '8/06/21 09:54:12',

            '08.06.2021 9:54:12', '8.6.2021 9:54:12', '08.6.2021 9:54:12', '8.06.2021 9:54:12',
            '08. 06. 2021 9:54:12', '8. 6. 2021 9:54:12', '08. 6. 2021 9:54:12', '8. 06. 2021 9:54:12',
            '08.06.21 9:54:12', '8.6.21 9:54:12', '08.6.21 9:54:12', '8.06.21 9:54:12',
            '08. 06. 21 9:54:12', '8. 6. 21 9:54:12', '08. 6. 21 9:54:12', '8. 06. 21 9:54:12',
            '08/06/2021 9:54:12', '8/6/2021 9:54:12', '08/6/2021 9:54:12', '8/06/2021 9:54:12',
            '08/06/21 9:54:12', '8/6/21 9:54:12', '08/6/21 9:54:12', '8/06/21 9:54:12',

            '2021-06-08 09:54:12', '2021-06-08T09:54:12', '2021-06-08T09:54:12Z',
            '2021-06-08T09:54:12+00:00', '2021-06-08T09:54:12+02:00', '2021-06-08T09:54:12.1234+02:00',
            strtotime($expected)
        ];

        foreach ($dates as $input) {
            $carbon = Date::parse($input);
            $actual = $carbon->format('Y-m-d H:i:s');
            self::assertSame($expected, $actual, 'Failed to correctly parse: ' . $input . ' - result: ' . $actual);
        }
    }

    public function testTimeMinutes(): void
    {
        $expected = '09:54';
        $dates = [
            '09:54', '9:54', '0954', strtotime($expected)
        ];

        foreach ($dates as $input) {
            $carbon = Date::parse($input);
            $actual = $carbon->format('H:i');
            self::assertSame($expected, $actual, 'Failed to correctly parse: ' . $input . ' - result: ' . $actual);
        }
    }

    public function testTimeSeconds(): void
    {
        $expected = '09:54:12';
        $dates = [
            '09:54:12', '9:54:12', strtotime($expected)
        ];

        foreach ($dates as $input) {
            $carbon = Date::parse($input);
            $actual = $carbon->format('H:i:s');
            self::assertSame($expected, $actual, 'Failed to correctly parse: ' . $input . ' - result: ' . $actual);
        }
    }

    public function testTimeTwelveHour(): void
    {
        $expected = '19:54:12';
        $dates = [
            '07:54:12pm', '7:54:12pm', '07:54:12 pm', '7:54:12 pm',
            '07:54:12PM', '7:54:12PM', '07:54:12 PM', '7:54:12 PM',
        ];

        foreach ($dates as $input) {
            $carbon = Date::parse($input);
            $actual = $carbon->format('H:i:s');
            self::assertSame($expected, $actual, 'Failed to correctly parse: ' . $input . ' - result: ' . $actual);
        }

        $expected = '07:54:12';
        $dates = [
            '07:54:12am', '7:54:12am', '07:54:12 am', '7:54:12 am',
            '07:54:12AM', '7:54:12AM', '07:54:12 AM', '7:54:12 AM',
        ];

        foreach ($dates as $input) {
            $carbon = Date::parse($input);
            $actual = $carbon->format('H:i:s');
            self::assertSame($expected, $actual, 'Failed to correctly parse: ' . $input . ' - result: ' . $actual);
        }
    }

    public function testFailure(): void
    {
        $dates = [
            'asdf', '', 0, false, null
        ];

        foreach ($dates as $input) {
            $carbon = Date::parse($input);
            self::assertNull($carbon, 'This date should be un-parsable and return null: ' . $input);
        }
    }

    public function testObject(): void
    {
        $expected = '2021-06-08 09:54:12';
        $carbon = Date::parse(new \DateTime($expected));
        $actual = $carbon->format('Y-m-d H:i:s');
        self::assertSame($expected, $actual, 'Failed to correctly parse DateTime object - result: ' . $actual);

        $expected = '2021-06-08 09:54:12';
        $carbon = Date::parse(Carbon::parse($expected));
        $actual = $carbon->format('Y-m-d H:i:s');
        self::assertSame($expected, $actual, 'Failed to correctly parse Carbon object - result: ' . $actual);
    }

    public function testTimeZone(): void
    {
        TimeZone::set(0);
        $carbon = Date::parse('2020-01-01 00:00:00');
        self::assertSame('Z', $carbon->format('p'));
    }

}