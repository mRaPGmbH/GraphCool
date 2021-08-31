<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

class ClassFinder
{
    protected static ?array $models;
    protected static ?array $queries;
    protected static ?array $mutations;
    protected static ?array $scripts;
    protected static ?string $appRootPath = null;

    public static function models(): array
    {
        if (!isset(static::$models)) {
            StopWatch::start(__METHOD__);
            static::$models = static::findClasses(self::rootPath() . '/app/Models', 'App\\Models\\');
            StopWatch::stop(__METHOD__);
        }
        return static::$models;
    }

    protected static function findClasses(string $path, string $namespace): array
    {
        $result = [];
        if (is_dir($path)) {
            $files = scandir($path);
            $classes = array_map(function ($file) {
                return str_replace('.php', '', $file);
            }, $files);
            foreach ($classes as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $classname = $namespace . $name;
                if (class_exists($classname)) {
                    $result[$name] = $classname;
                }
            }
        }
        return $result;
    }

    public static function rootPath(): string
    {
        if (!isset(static::$appRootPath)) {
            $levels = 2;
            if (PHP_SAPI === 'cli') {
                $levels = 1;
            }
            $path = dirname($_SERVER['SCRIPT_FILENAME'], $levels);
            if ($path === '.') {
                $path = $_SERVER['PWD'];
            }
            static::$appRootPath = $path;
        }
        return static::$appRootPath;
    }

    public static function setRootPath(?string $appRootPath)
    {
        static::$models = null;
        static::$queries = null;
        static::$mutations = null;
        static::$scripts = null;
        static::$appRootPath = $appRootPath;
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

    public static function scripts(): array
    {
        if (!isset(static::$scripts)) {
            StopWatch::start(__METHOD__);
            static::$scripts = static::findClasses(self::rootPath() . '/app/Scripts', 'App\\Scripts\\');
            StopWatch::stop(__METHOD__);
        }
        return static::$scripts;
    }

}