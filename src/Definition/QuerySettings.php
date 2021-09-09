<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

class QuerySettings
{
    public const QUERY = 'QUERY';
    public const MUTATION = 'MUTATION';

    public const PUBLIC = 'PUBLIC';
    public const USER = 'USER';
    public const ADMIN = 'ADMIN';

    /** @var string */
    public $type;

    /** @var string */
    public $access;

    public function __construct(string $type = self::QUERY, string $access = self::USER)
    {
        $this->type = $type;
        $this->access = $access;
    }

}