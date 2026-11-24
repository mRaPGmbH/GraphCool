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

    /** @var callable(string): bool  replaceable in tests, where no real upload exists */
    public static $isUploadedFile = 'is_uploaded_file';

    public string $name = '_Upload';
    public ?string $description = 'A file to be uploaded (as multipart/blob).';

    public function serialize(mixed $value): void
    {
        throw new Error('File upload can only be used as input.');
    }

    /**
     * Only accepts the $_FILES entry that FileUpload::parse() put into the variables. A client
     * can send the same shape as plain JSON variables, with any server path as tmp_name, so the
     * file must be one PHP received in this request.
     */
    public function parseValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        $tmpName = is_array($value) ? ($value['tmp_name'] ?? null) : null;
        if (!is_string($tmpName) || !(self::$isUploadedFile)($tmpName)) {
            throw new Error('File upload failed or was not sent as multipart/form-data.');
        }
        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): ?string
    {
        return null;
    }
}
