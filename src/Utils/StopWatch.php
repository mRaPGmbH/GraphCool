<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

class StopWatch
{
    protected static float $firstStart;

    /** @var float[] */
    protected static array $starts = [];

    /** @var float[][] */
    protected static array $times = [];

    public static function start(string $name): void
    {
        self::$starts[$name] = microtime(true);
        if (!isset(self::$firstStart)) {
            self::$firstStart = self::$starts[$name];
        }
        if (!isset(self::$times[$name])) {
            self::$times[$name] = [];
        }
    }

    public static function stop(string $name): void
    {
        if (!isset(self::$starts[$name])) {
            return;
        }
        self::$times[$name][] = microtime(true) - self::$starts[$name];
        unset(self::$starts[$name]);
    }

    /**
     * @return array[]
     */
    public static function get(): array
    {
        $result = [];
        $total = microtime(true) - self::$firstStart;
        foreach (self::$times as $key => $values) {
            $count = count($values);
            if ($count === 0) {
                $result[$key] = [
                    'count' => 0,
                    'sum' => 0,
                    'avg' => 0,
                    'share' => 0,
                    'max' => 0,
                    'min' => 0
                ];
                continue;
            }
            $sum = array_sum($values);
            $result[$key] = [
                'count' => $count,
                'sum' => $sum * 1000,
                'avg' => 1000 * $sum / $count,
                'share' => $sum / $total,
                'max' => 1000 * max($values),
                'min' => 1000 * min($values)
            ];
        }
        return $result;
    }

    public static function reset(): void
    {
        self::$starts = [];
        self::$times = [];
    }

}
