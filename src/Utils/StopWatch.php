<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Utils;


class StopWatch
{
    protected static array $starts = [];
    protected static array $times = [];

    public static function start($name): void
    {
        self::$starts[$name] = microtime(true);
        if (!isset(self::$times[$name])) {
            self::$times[$name] = .0;
        }
    }

    public static function stop($name): void
    {
        self::$times[$name] += 1000 * (microtime(true) - self::$starts[$name]);
    }

    public static function get(): array
    {
        arsort(self::$times);
        $percentages = [];
        foreach (self::$times as $key => $value) {
            $percentages[$key] = round($value / self::$times['Mrap\\GraphCool\\GraphCool::run'] * 100, 2) . '%';
        }
        return [self::$times, $percentages];
    }

}