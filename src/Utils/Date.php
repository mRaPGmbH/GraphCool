<?php


namespace Mrap\GraphCool\Utils;


use Carbon\Carbon;
use Mrap\GraphCool\GraphCool;
use RuntimeException;
use Throwable;

class Date
{

    public static function parse(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        $timezone = TimeZone::get();
        if ($timezone === '+00:00') {
            $timezone = null;
        }

        if (is_string($value)) {
            $fragments = [
                'd' => '[0-9]{2}',
                'j' => '[0-9]{1,2}',

                'm' => '[0-9]{2}',
                'n' => '[0-9]{1,2}',

                'Y' => '[0-9]{4}',
                'y' => '[0-9]{2}',

                'H' => '[0-9]{2}',
                'h' => '[0-9]{2}',

                'G' => '[0-9]{1,2}',
                'g' => '[0-9]{1,2}',

                'i' => '[0-9]{2}',
                's' => '[0-9]{2}',

                'a' => '(am|pm)',
                'A' => '(AM|PM)',
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

                'm/d/Y',
                'm/d/Y h:ia',
                'm/d/Y h:i a',
                'm/d/Y h:iA',
                'm/d/Y h:i A',
                'm/d/Y h:i:sa',
                'm/d/Y h:i:s a',
                'm/d/Y h:i:sA',
                'm/d/Y h:i:s A',
                'm/d/Y g:ia',
                'm/d/Y g:i a',
                'm/d/Y g:iA',
                'm/d/Y g:i A',
                'm/d/Y g:i:sa',
                'm/d/Y g:i:s a',
                'm/d/Y g:i:sA',
                'm/d/Y g:i:s A',
                'm/d/Y H:i',
                'm/d/Y Hi',
                'm/d/Y H:i:s',
                'm/d/Y G:i',
                'm/d/Y G:i:s',

                'm/d/y',
                'm/d/y h:ia',
                'm/d/y h:i a',
                'm/d/y h:iA',
                'm/d/y h:i A',
                'm/d/y h:i:sa',
                'm/d/y h:i:s a',
                'm/d/y h:i:sA',
                'm/d/y h:i:s A',
                'm/d/y g:ia',
                'm/d/y g:i a',
                'm/d/y g:iA',
                'm/d/y g:i A',
                'm/d/y g:i:sa',
                'm/d/y g:i:s a',
                'm/d/y g:i:sA',
                'm/d/y g:i:s A',
                'm/d/y H:i',
                'm/d/y Hi',
                'm/d/y H:i:s',
                'm/d/y G:i',
                'm/d/y G:i:s',

                'n/j/Y',
                'n/j/Y h:ia',
                'n/j/Y h:i a',
                'n/j/Y h:iA',
                'n/j/Y h:i A',
                'n/j/Y h:i:sa',
                'n/j/Y h:i:s a',
                'n/j/Y h:i:sA',
                'n/j/Y h:i:s A',
                'n/j/Y g:ia',
                'n/j/Y g:i a',
                'n/j/Y g:iA',
                'n/j/Y g:i A',
                'n/j/Y g:i:sa',
                'n/j/Y g:i:s a',
                'n/j/Y g:i:sA',
                'n/j/Y g:i:s A',
                'n/j/Y H:i',
                'n/j/Y Hi',
                'n/j/Y H:i:s',
                'n/j/Y G:i',
                'n/j/Y G:i:s',

                'n/j/y',
                'n/j/y h:ia',
                'n/j/y h:i a',
                'n/j/y h:iA',
                'n/j/y h:i A',
                'n/j/y h:i:sa',
                'n/j/y h:i:s a',
                'n/j/y h:i:sA',
                'n/j/y h:i:s A',
                'n/j/y g:ia',
                'n/j/y g:i a',
                'n/j/y g:iA',
                'n/j/y g:i A',
                'n/j/y g:i:sa',
                'n/j/y g:i:s a',
                'n/j/y g:i:sA',
                'n/j/y g:i:s A',
                'n/j/y H:i',
                'n/j/y Hi',
                'n/j/y H:i:s',
                'n/j/y G:i',
                'n/j/y G:i:s',

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

                try {
                    if (preg_match($pattern, $value)) {
                        return Carbon::createFromFormat($format, $value, $timezone);
                    }
                } catch (Throwable $e) {
                    var_dump($pattern);
                    var_dump($e->getMessage());
                    echo $e->getFile() . ':' . $e->getLine();
                }
            }
        }

        try {
            return Carbon::parse($value, $timezone);
        } catch (Throwable $e) {
            $e = new RuntimeException('Could not parse date: ' . $value, 0, $e);
            GraphCool::sentryCapture($e);
            return null;
        }
    }


}