<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Mrap\GraphCool\Utils\TimeZone;
use Throwable;

class DateTime extends ScalarType
{

    public $name = '_DateTime';
    public $description = 'A DateTime string with timezone in following ISO 8601 format: "2021-03-11T11:54:04.123+00:00". When used as input several other ISO 8601 variants are accepted as well.';

    public function serialize(mixed $value): string
    {
        return static::getObject($value)->format('Y-m-d\TH:i:s.vp');
    }

    public static function getObject(mixed $value): Carbon
    {
        $dateTime = Carbon::createFromTimestampMs($value);
        $dateTime->setTimezone(TimeZone::get());
        return $dateTime;
    }

    public function parseValue(mixed $value): ?int
    {
        return $this->validate($value);
    }

    protected function validate(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        try {
            $dateTime = Carbon::parse($value);
        } catch (Throwable $e) {
            throw new Error('Could not parse _DateTime: ' . ((string)$value));
        }
        return (int)$dateTime->getPreciseTimestamp(3);
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): int
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings but got: ' . $valueNode->kind, $valueNode);
        }
        return $this->validate($valueNode->value);
    }

}
