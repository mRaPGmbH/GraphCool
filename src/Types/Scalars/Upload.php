<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class Upload extends ScalarType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_Upload';
    }

    public $name = '_Upload';
    public $description = 'A file to be uploaded (as multipart/blob).';

    public function serialize(mixed $value): void
    {
        throw new Error('File upload can only be used as input.');
    }

    public function parseValue(mixed $value): mixed
    {
        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): ?string
    {
        return null;
    }
}
