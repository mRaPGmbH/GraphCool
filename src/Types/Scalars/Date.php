<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Throwable;

class Date extends ScalarType
{

    public $name = '_Date';
    public $description = 'A Date string in ISO 8601 format: "2021-03-11"';

    public function serialize(mixed $value): string
    {
        return static::getObject($value)->format('Y-m-d');
    }

    public static function getObject(mixed $value): Carbon
    {
        $dateTime = Carbon::createFromTimestampMs($value);
        $dateTime->setTimezone("+0000");
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
            $dateTime = Carbon::parse($value, "+0000");
        } catch (Throwable $e) {
            throw new Error('Could not parse _Date: ' . ((string)$value));
        }
        return (int)$dateTime->getPreciseTimestamp(3);
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): ?int
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings but got: ' . $valueNode->kind, $valueNode);
        }
        return $this->validate($valueNode->value);
    }

}
