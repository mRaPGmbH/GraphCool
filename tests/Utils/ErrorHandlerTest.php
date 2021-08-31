<?php

namespace Mrap\GraphCool\Tests\Utils;

use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\ErrorHandler;

class ErrorHandlerTest extends TestCase
{
    public function testHandleError(): void
    {
        $e = new \Exception('test');
        $result = ErrorHandler::handleError($e);
        self::assertEquals('Internal server error', $result['errors'][0]['message'] ?? null);
    }

    public function testClosure(): void
    {
        $closure = ErrorHandler::getClosure();
        $e = new \Exception('test');
        $result = $closure([$e], 'GraphQL\Error\FormattedError::createFromException');

        self::assertEquals('Internal server error', $result[0]['message'] ?? null);
    }
}