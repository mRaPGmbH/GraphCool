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

class Time extends ScalarType
{

    public $name = '_Time';
    public $description = 'A Time string in ISO 8601 format: "11:54:04+00:00" or "11:54:04Z"';

    public static function getObject(mixed $value): Carbon
    {
        $dateTime = Carbon::createFromTimestampMs($value);
        $dateTime->setTimezone(TimeZone::get());
        return $dateTime;
    }

    public function serialize(mixed $value): string
    {
        $dateTime = Carbon::createFromTimestampMs($value);
        $dateTime->setTimezone(TimeZone::get());
        return $dateTime->format('H:i:s.vp');
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
            throw new Error('Could not parse _Time: ' . ((string)$value));
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
