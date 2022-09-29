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

    /**
     * @param Throwable $e
     * @return array[]
     * @throws Throwable
     */
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
        $environment = Env::get('APP_ENV');
        if ($environment === 'local') {
            echo print_r($e, true);
            return;
        }
        $sentryDsn = Env::get('SENTRY_DSN');
        if ($sentryDsn !== null && function_exists("\Sentry\init")) {
            \Sentry\init([
                'dsn' => $sentryDsn,
                'environment' => Env::get('APP_ENV'),
                'release' => Env::get('APP_NAME') . '@' . Env::get('APP_VERSION'),
                'server_name' => Env::get('APP_NAME')
            ]);
            \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                $scope->setUser([
                    'id' => JwtAuthentication::tenantId(),
                    'username' => JwtAuthentication::getClaim('sub'),
                    'ip' => ClientInfo::ip(),
                ]);
            });
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