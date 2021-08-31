<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Model;

class QuerySettings
{
    public const QUERY = 'QUERY';
    public const MUTATION = 'MUTATION';

    public const PUBLIC = 'PUBLIC';
    public const USER = 'USER';
    public const ADMIN = 'ADMIN';

    public $type;
    public $access;

    public function __construct(string $type = self::QUERY, string $access = self::USER)
    {
        $this->type = $type;
        $this->access = $access;
    }

}