<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

class ClientInfo
{
    public static function ip(): ?string
    {
        return /* $_SERVER['HTTP_CLIENT_IP'] ?? */ $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public static function user_agent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}