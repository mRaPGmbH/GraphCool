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
    $models = ClassFinder::models();
    if (isset($models[$name])) {
        try {
            return new ($models[$name])();
        } catch (\Error) {}
    }
    throw new RuntimeException('Unknown entity: ' . $name);
}
