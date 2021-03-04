<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

//StopWatch::start('.env');
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../../..');
$dotenv->load();
//StopWatch::stop('.env');

class Env
{
    public static function get(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $default;
        }
        $value = trim($value);
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