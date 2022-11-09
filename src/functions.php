<?php

declare(strict_types=1);

namespace Mrap\GraphCool;

use GraphQL\Error\Error;
use Mrap\GraphCool\Definition\Model;

/**
 * @throws Error
 */
function model(string $name): Model
{
    if ($name === 'BlacklistedEmails') {
        throw new \RuntimeException('SDFSDFSDF');
    }

    try {
        return (new ('App\\Models\\' . $name)())->injectFieldNames();
    } catch (\Error) {
        throw new Error('Unknown entity: ' . $name);
    }
}

function pluralize(string $name): string
{
    return $name . 's';
}
