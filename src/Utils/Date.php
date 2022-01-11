<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Carbon\Carbon;
use GraphQL\Error\Error;
use RuntimeException;
use Throwable;

class Date
{

    public static function parseToInt(mixed $value, bool $forceUtc = false): int
    {
        $carbon = static::parse($value, $forceUtc);
        if ($carbon === null) {
            throw new Error('Could not parse date value: ' . ((string)$value));
        }
        return (int)$carbon->getPreciseTimestamp(3);
    }

    public static function parse(mixed $value, bool $forceUtc = false): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        if ($forceUtc) {
            $timezone = null;
        } else {
            $timezone = TimeZone::get();
            if ($timezone === '+00:00') {
                $timezone = null;
            }
        }

        if (is_string($value)) {
            $fragments = [
                'd' => '[0-9]{2}', // day 01 - 31
                'j' => '[0-9]{1,2}', // day 1 -31

                'm' => '[0-9]{2}', // month 01 - 12
                'n' => '[0-9]{1,2}', // month 1 - 12

                'Y' => '[0-9]{4}', // year 1984
                'y' => '[0-9]{2}', // year 84

                'H' => '[0-9]{2}', // hour 00 - 23
                'h' => '[0-9]{2}', // hour 01 - 12

                'G' => '[0-9]{1,2}', // hour 0 - 23
                'g' => '[0-9]{1,2}', // hour 1 - 12

                'i' => '[0-9]{2}', // minutes 00 - 59
                's' => '[0-9]{2}', // seconds 00 - 59

                'a' => '(am|pm)', // am or pm
                'A' => '(AM|PM)', // AM or PM
            ];

            $formats = [
                'd.m.Y',
                'd.m.Y H:i',
                'd.m.Y H:i:s',
                'd.m.Y G:i',
                'd.m.Y G:i:s',

                'd. m. Y',
                'd. m. Y H:i',
                'd. m. Y H:i:s',
                'd. m. Y G:i',
                'd. m. Y G:i:s',

                'd.m.y',
                'd.m.y H:i',
                'd.m.y H:i:s',
                'd.m.y G:i',
                'd.m.y G:i:s',

                'd. m. y',
                'd. m. y H:i',
                'd. m. y H:i:s',
                'd. m. y G:i',
                'd. m. y G:i:s',

                'j.n.Y',
                'j.n.Y H:i',
                'j.n.Y H:i:s',
                'j.n.Y G:i',
                'j.n.Y G:i:s',

                'j. n. Y',
                'j. n. Y H:i',
                'j. n. Y H:i:s',
                'j. n. Y G:i',
                'j. n. Y G:i:s',

                'j.n.y',
                'j.n.y H:i',
                'j.n.y H:i:s',
                'j.n.y G:i',
                'j.n.y G:i:s',

                'j. n. y',
                'j. n. y H:i',
                'j. n. y H:i:s',
                'j. n. y G:i',
                'j. n. y G:i:s',

                'Y-m-d',
                'Y-m-d H:i',
                'Y-m-d H:i:s',
                'Y-m-d\TH:i:s',

                'd/m/Y',
                'd/m/Y h:ia',
                'd/m/Y h:i a',
                'd/m/Y h:iA',
                'd/m/Y h:i A',
                'd/m/Y h:i:sa',
                'd/m/Y h:i:s a',
                'd/m/Y h:i:sA',
                'd/m/Y h:i:s A',
                'd/m/Y g:ia',
                'd/m/Y g:i a',
                'd/m/Y g:iA',
                'd/m/Y g:i A',
                'd/m/Y g:i:sa',
                'd/m/Y g:i:s a',
                'd/m/Y g:i:sA',
                'd/m/Y g:i:s A',
                'd/m/Y H:i',
                'd/m/Y Hi',
                'd/m/Y H:i:s',
                'd/m/Y G:i',
                'd/m/Y G:i:s',

                'd/m/y',
                'd/m/y h:ia',
                'd/m/y h:i a',
                'd/m/y h:iA',
                'd/m/y h:i A',
                'd/m/y h:i:sa',
                'd/m/y h:i:s a',
                'd/m/y h:i:sA',
                'd/m/y h:i:s A',
                'd/m/y g:ia',
                'd/m/y g:i a',
                'd/m/y g:iA',
                'd/m/y g:i A',
                'd/m/y g:i:sa',
                'd/m/y g:i:s a',
                'd/m/y g:i:sA',
                'd/m/y g:i:s A',
                'd/m/y H:i',
                'd/m/y Hi',
                'd/m/y H:i:s',
                'd/m/y G:i',
                'd/m/y G:i:s',

                'j/n/Y',
                'j/n/Y h:ia',
                'j/n/Y h:i a',
                'j/n/Y h:iA',
                'j/n/Y h:i A',
                'j/n/Y h:i:sa',
                'j/n/Y h:i:s a',
                'j/n/Y h:i:sA',
                'j/n/Y h:i:s A',
                'j/n/Y g:ia',
                'j/n/Y g:i a',
                'j/n/Y g:iA',
                'j/n/Y g:i A',
                'j/n/Y g:i:sa',
                'j/n/Y g:i:s a',
                'j/n/Y g:i:sA',
                'j/n/Y g:i:s A',
                'j/n/Y H:i',
                'j/n/Y Hi',
                'j/n/Y H:i:s',
                'j/n/Y G:i',
                'j/n/Y G:i:s',

                'j/n/y',
                'j/n/y h:ia',
                'j/n/y h:i a',
                'j/n/y h:iA',
                'j/n/y h:i A',
                'j/n/y h:i:sa',
                'j/n/y h:i:s a',
                'j/n/y h:i:sA',
                'j/n/y h:i:s A',
                'j/n/y g:ia',
                'j/n/y g:i a',
                'j/n/y g:iA',
                'j/n/y g:i A',
                'j/n/y g:i:sa',
                'j/n/y g:i:s a',
                'j/n/y g:i:sA',
                'j/n/y g:i:s A',
                'j/n/y H:i',
                'j/n/y Hi',
                'j/n/y H:i:s',
                'j/n/y G:i',
                'j/n/y G:i:s',

                'H:i',
                'Hi',
                'H:i:s',
                'G:i',
                'G:i:s',

                'h:ia',
                'h:i a',
                'h:iA',
                'h:i A',
                'h:i:sa',
                'h:i:s a',
                'h:i:sA',
                'h:i:s A',

                'g:ia',
                'g:i a',
                'g:iA',
                'g:i A',
                'g:i:sa',
                'g:i:s a',
                'g:i:sA',
                'g:i:s A',
            ];

            foreach ($formats as $format) {
                $pattern = '!^' . str_replace(array_keys($fragments), array_values($fragments), $format) . '$!';
                $pattern = str_replace(['\\', '.'], ['', '\.'], $pattern);

                if (preg_match($pattern, $value)) {
                    $result = Carbon::createFromFormat($format, $value, $timezone);
                    if ($result === false) {
                        return null;
                    }
                    return $result;
                }
            }
        }

        try {
            return Carbon::parse($value, $timezone);
        } catch (Throwable $e) {
            return null;
        }
    }


}