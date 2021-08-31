<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Closure;
use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use Mrap\GraphCool\GraphCool;
use Throwable;

class ErrorHandler
{

    public static function handleError(Throwable $e): array
    {
        if (!$e instanceof ClientAware || !$e->isClientSafe()) {
            static::sentryCapture($e);
        }
        return [
            'errors' => [
                FormattedError::createFromException($e, GraphCool::getDebugFlags())
            ]
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public static function sentryCapture(Throwable $e): void
    {
        $sentryDsn = Env::get('SENTRY_DSN');
        $environment = Env::get('APP_ENV');
        if ($sentryDsn !== null && $environment !== 'local' && function_exists("\Sentry\init")) {
            \Sentry\init([
                'dsn' => $sentryDsn,
                'environment' => Env::get('APP_ENV'),
                'release' => Env::get('APP_NAME') . '@' . Env::get('APP_VERSION'),
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'n/a'
            ]);
            \Sentry\captureException($e);
        }
    }

    public static function getClosure(): Closure
    {
        return function (array $errors, callable $formatter) {
            /** @var Error $error */
            foreach ($errors as $error) {
                if (!method_exists($error, 'isClientSafe') || !$error->isClientSafe()) {
                    $previous = $error->getPrevious();
                    if ($previous === null) {
                        ErrorHandler::sentryCapture($error);
                    } else {
                        ErrorHandler::sentryCapture($previous);
                    }
                }
            }
            return array_map($formatter, $errors);
        };
    }

}