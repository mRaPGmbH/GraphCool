<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

class Utf8
{
    public static function sanitize(string $input): string
    {
        // https://www.w3.org/International/questions/qa-forms-utf-8.en
        if (preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
        )*$%xs', $input)) {
            return $input;
        }
        return iconv('CP1252', 'UTF-8', $input);
    }


}