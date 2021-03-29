<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

class ClassFinder
{
    protected static $models;
    protected static $queries;
    protected static $mutations;

    public static function models(): array
    {
        if (!isset(static::$models)) {
            StopWatch::start(__METHOD__);
            static::$models = static::findClasses(self::rootPath() . '/app/Models', 'App\\Models\\');
            StopWatch::stop(__METHOD__);
        }
        return static::$models;
    }

    public static function rootPath(): string
    {
        return dirname($_SERVER['SCRIPT_FILENAME'], 2);
    }

    public static function queries(): array
    {
        if (!isset(static::$queries)) {
            StopWatch::start(__METHOD__);
            static::$queries = static::findClasses(self::rootPath() . '/app/Queries', 'App\\Queries\\');
            StopWatch::stop(__METHOD__);
        }
        return static::$queries;
    }

    public static function mutations(): array
    {
        if (!isset(static::$mutations)) {
            StopWatch::start(__METHOD__);
            static::$mutations = static::findClasses(self::rootPath() . '/app/Mutations', 'App\\Mutations\\');
            StopWatch::stop(__METHOD__);
        }
        return static::$mutations;
    }

    protected static function findClasses(string $path, string $namespace): array
    {
        $result = [];
        if (is_dir($path)) {
            $files = scandir($path);
            $classes = array_map(function($file){
                return str_replace('.php', '', $file);
            }, $files);
            foreach ($classes as $name) {
                $classname = $namespace . $name;
                if (class_exists($classname)) {
                    $result[$name] = $classname;
                }
            }
        }
        return $result;
    }

}