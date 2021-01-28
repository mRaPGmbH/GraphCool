<?php


namespace Mrap\GraphCool\Utils;


class Env
{
    public static function get(string $key, $default = null)
    {
        $value = trim($_ENV[$key] ?? $default);
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
            return $matches[2];
        }
        return $value;
    }
}