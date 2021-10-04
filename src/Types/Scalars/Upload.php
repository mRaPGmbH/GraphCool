<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;

class Upload extends ScalarType
{

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