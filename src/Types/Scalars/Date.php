<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class Date extends ScalarType
{

    public $name = '_Date';
    public $description = 'A Date string in ISO 8601 format: "2021-03-11"';

    public static function getObject($value): Carbon
    {
        $dateTime = Carbon::createFromTimestampMs($value);
        $dateTime->setTimezone("+0000");
        return $dateTime;
    }

    public function serialize($value): string
    {
        $dateTime = Carbon::createFromTimestampMs($value);
        $dateTime->setTimezone("+0000");
        return $dateTime->format('Y-m-d');
    }

    public function parseValue($value): ?int
    {
        return $this->validate($value);
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): ?int
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings but got: ' . $valueNode->kind, [$valueNode]);
        }
        return $this->validate($valueNode->value);
    }

    protected function validate($value): ?int
    {
        if ($value === null) {
            return null;
        }
        $dateTime = Carbon::parse($value, "+0000");
        return (int)$dateTime->getPreciseTimestamp(3);
    }

}