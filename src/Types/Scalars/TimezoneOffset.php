<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Mrap\GraphCool\Utils\TimeZone;

class TimezoneOffset extends ScalarType
{

    public $name = '_TimezoneOffset';
    public $description = 'A Timezone offset string in ISO 8601 format: "+00:00" or "Z"';

    public function serialize($value): string
    {
        return TimeZone::serialize((int) $value);
    }

    public function parseValue($value): int
    {
        return $this->validate($value);
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): int
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings but got: ' . $valueNode->kind, [$valueNode]);
        }
        return $this->validate($valueNode->value);
    }

    protected function validate(string $value): int
    {
        try {
            $dateTime = Carbon::parse($value);
        } catch (\Throwable $e) {
            throw new Error('Could not parse _TimezoneOffset: ' . ((string)$value));
        }
        return $dateTime->getOffset();
    }

}