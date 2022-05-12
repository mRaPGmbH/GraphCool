<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

class StopWatch
{
    /** @var float[] */
    protected static array $starts = [];

    /** @var float[] */
    protected static array $times = [];

    public static function start(string $name): void
    {
        self::$starts[$name] = microtime(true);
        if (!isset(self::$times[$name])) {
            self::$times[$name] = .0;
        }
    }

    public static function stop(string $name): void
    {
        self::$times[$name] += 1000 * (microtime(true) - self::$starts[$name]);
    }

    /**
     * @return array[]
     */
    public static function get(): array
    {
        arsort(self::$times);
        $percentages = [];
        foreach (self::$times as $key => $value) {
            $percentages[$key] = round($value / (self::$times['Mrap\\GraphCool\\GraphCool::run'] ?? 1) * 100, 2) . '%';
        }
        return [self::$times, $percentages];
    }

    public static function reset(): void
    {
        self::$starts = [];
        self::$times = [];
    }

}
