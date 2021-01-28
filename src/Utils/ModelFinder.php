<?php

namespace Mrap\GraphCool\Utils;

class ModelFinder
{
    protected static $models;

    public static function all(): array
    {
        if (!isset(static::$models)) {
            $files = scandir(__DIR__ . '/../../../../../app/Models');
            $classes = array_map(function($file){
                return str_replace('.php', '', $file);
            }, $files);
            static::$models = array_filter($classes, function($possibleClass){
                $classname = 'App\\Models\\' . $possibleClass;
                return class_exists($classname);
            });
        }
        return static::$models;
    }

}