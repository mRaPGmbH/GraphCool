<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use RuntimeException;

class ClassFinder
{
    /** @var string[]|null */
    protected static ?array $models;
    protected static ?array $modelPaths = [];

    /** @var string[]|null */
    protected static ?array $queries;
    protected static ?array $queryPaths = [];

    /** @var string[]|null */
    protected static ?array $mutations;
    protected static ?array $mutationPaths = [];

    /** @var string[]|null */
    protected static ?array $scripts;
    protected static ?array $scriptPaths = [];

    protected static ?string $appRootPath = null;

    /**
     * @return string[]
     */
    public static function models(): array
    {
        if (!isset(static::$models)) {
            // TODO: move this into app
            static::registerModelPath(self::rootPath() . '/app/Models');

            StopWatch::start(__METHOD__);
            static::$models = static::findClasses(self::$modelPaths);
            StopWatch::stop(__METHOD__);
        }
        return static::$models;
    }

    public static function registerModelPath(string $path, string $namespace = 'App\\Models\\'): void
    {
        static::$modelPaths[$path] = $namespace;
    }
    public static function registerQueryPath(string $path, string $namespace = 'App\\Queries\\'): void
    {
        static::$queryPaths[$path] = $namespace;
    }
    public static function registerMutationPath(string $path, string $namespace = 'App\\Mutations\\'): void
    {
        static::$mutationPaths[$path] = $namespace;
    }
    public static function registerScriptPath(string $path, string $namespace = 'App\\Scripts\\'): void
    {
        static::$scriptPaths[$path] = $namespace;
    }

    /**
     * @param string $path
     * @param string $namespace
     * @return string[]
     */
    protected static function findClasses(array $paths): array
    {
        $result = [];
        foreach ($paths as $path => $namespace) {
            if (!is_dir($path)) {
                throw new RuntimeException('Trying to search classes in non-existing path: ' . $path);
            }
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
                    if (isset($result[$name])) {
                        throw new RuntimeException('Duplicate Model "' . $name . '": '. $result[$name] . ' + ' . $classname);
                    }
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
            // TODO: move this into app
            static::registerQueryPath(self::rootPath() . '/app/Queries');

            StopWatch::start(__METHOD__);
            static::$queries = static::findClasses(self::$queryPaths);
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
            // TODO: move this into app
            static::registerMutationPath(self::rootPath() . '/app/Mutations');

            StopWatch::start(__METHOD__);
            static::$mutations = static::findClasses(self::$mutationPaths);
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
            // TODO: move this into app
            static::registerScriptPath(self::rootPath() . '/app/Scripts');

            StopWatch::start(__METHOD__);
            static::$scripts = static::findClasses(self::$scriptPaths);
            StopWatch::stop(__METHOD__);
        }
        return static::$scripts;
    }

}