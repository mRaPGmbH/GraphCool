<?php


namespace Mrap\GraphCool\Utils;

use DateTimeZone;

class TimeZone
{
    protected static ?DateTimeZone $timeZone = null;

    public static function get(): DateTimeZone
    {
        if (static::$timeZone === null) {
            static:: $timeZone = new DateTimeZone("+0000");
        }
        return static::$timeZone;
    }

    public static function set(int $offset): void
    {
        static::$timeZone = new DateTimeZone(static::serialize($offset));
    }

    public static function serialize(int $offset): string
    {
        if ($offset < 0) {
            $sign = '-';
        } else {
            $sign = '+';
        }
        $offset = (int) round(abs($offset) / 60);
        $minutes = $offset % 60;
        $hours = (int) floor($offset / 60);

        $string = $sign
            . str_pad((string)$hours, 2, '0', STR_PAD_LEFT)
            . ':'
            . str_pad((string)$minutes, 2, '0', STR_PAD_LEFT);

        $dateTime = new \DateTime();
        $dateTime->setTimezone(new \DateTimeZone($string));
        return $dateTime->format('P');
    }

}