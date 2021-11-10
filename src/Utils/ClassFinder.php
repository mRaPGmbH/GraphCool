<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Definition\Script;
use RuntimeException;

class ClassFinder
{
    /** @var string[]|null */
    protected static ?array $models;

    /** @var string[]|null */
    protected static ?array $queries;

    /** @var string[]|null */
    protected static ?array $mutations;

    /** @var string[]|null */
    protected static ?array $scripts;

    protected static ?string $appRootPath = null;

    /**
     * @return string[]
     */
    public static function models(): array
    {
        if (!isset(static::$models)) {
            StopWatch::start(__METHOD__);
            static::$models = static::findClasses(self::rootPath() . '/app/Models', 'App\\Models\\');
            StopWatch::stop(__METHOD__);
        }
        return static::$models;
    }

    /**
     * @param string $path
     * @param string $namespace
     * @return string[]
     */
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

    public static function setRootPath(?string $appRootPath): void
    {
        static::$models = null;
        static::$queries = null;
        static::$mutations = null;
        static::$scripts = null;
        static::$appRootPath = $appRootPath;
    }

    /**
     * @return string[]
     */
    public static function queries(): array
    {
        if (!isset(static::$queries)) {
            StopWatch::start(__METHOD__);
            static::$queries = static::findClasses(self::rootPath() . '/app/Queries', 'App\\Queries\\');
            StopWatch::stop(__METHOD__);
        }
        return static::$queries;
    }

    /**
     * @return string[]
     */
    public static function mutations(): array
    {
        if (!isset(static::$mutations)) {
            StopWatch::start(__METHOD__);
            static::$mutations = static::findClasses(self::rootPath() . '/app/Mutations', 'App\\Mutations\\');
            StopWatch::stop(__METHOD__);
        }
        return static::$mutations;
    }

    /**
     * @return string[]
     */
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