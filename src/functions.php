<?php

declare(strict_types=1);

namespace Mrap\GraphCool;

use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Utils\ClassFinder;
use RuntimeException;

/**
 * @throws RuntimeException
 */
function model(string $name): Model
{
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
