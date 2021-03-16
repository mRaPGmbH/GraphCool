<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Mrap\GraphCool\Utils\TimeZone;

class DateTime extends ScalarType
{

    public $name = '_DateTime';
    public $description = 'A DateTime string with timezone in one of following ISO 8601 formats: "2021-03-11T11:54:04.123+00:00", "2021-03-11T11:54:04.123Z", "2021-03-11T11:54:04.123+0000", "2021-03-11T11:54:04.123+00", "2021-03-11T11:54:04+00:00", "2021-03-11T11:54:04Z", "2021-03-11T11:54:04+0000", "2021-03-11T11:54:04+00" ';

    public function serialize($value): string
    {
        $dateTime = Carbon::createFromTimestampMs($value);
        $dateTime->setTimezone(TimeZone::get());
        return $dateTime->format('Y-m-d\TH:i:s.vp');
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
        $dateTime = Carbon::parse($value);
        return (int)$dateTime->getPreciseTimestamp(3);
    }

}