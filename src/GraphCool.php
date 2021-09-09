<?php

declare(strict_types=1);

namespace Mrap\GraphCool;

use Closure;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use JsonException;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\Script;
use Mrap\GraphCool\Types\MutationType;
use Mrap\GraphCool\Types\QueryType;
use Mrap\GraphCool\Types\TypeLoader;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\ErrorHandler;
use Mrap\GraphCool\Utils\StopWatch;
use RuntimeException;
use Throwable;

class GraphCool
{
    /** @var Closure[] */
    protected static array $shutdown = [];

    public static function run(): void
    {
        Env::init();
        StopWatch::start(__METHOD__);
        $instance = new self();
        $result = [];
        try {
            $request = $instance->parseRequest();
            $schema = $instance->createSchema();
            foreach ($request as $index => $query) {
                $result = $instance->executeQuery($schema, $query['query'], $query['variables'] ?? [], $index);
            }
        } catch (Throwable $e) {
            $result = ErrorHandler::handleError($e);
        }
        StopWatch::stop(__METHOD__);
        $instance->sendResponse($result);

        foreach (static::$shutdown as $closure) {
            $closure();
        }
    }

    /**
     * @return mixed[]
     * @throws Error
     */
    protected function parseRequest(): array
    {
        StopWatch::start(__METHOD__);
        $raw = file_get_contents('php://input');
        if (empty($raw) && isset($_POST['operations'])) {
            $raw = $_POST['operations'];
        }
        if (empty($raw)) {
            StopWatch::stop(__METHOD__);
            throw new Error('Syntax Error: Unexpected <EOF>');
        }
        try {
            $request = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            StopWatch::stop(__METHOD__);
            throw new Error('Syntax Error: Unexpected <EOF>');
        }
        if (isset($request['query'])) {
            $request = [$request];
        }
        StopWatch::stop(__METHOD__);
        return $request;
    }

    protected function createSchema(): Schema
    {
        StopWatch::start(__METHOD__);
        $typeLoader = new TypeLoader();
        $schema = new Schema(
            [
                'query' => new QueryType($typeLoader),
                'mutation' => new MutationType($typeLoader),
                'typeLoader' => $this->getTypeLoaderClosure($typeLoader),
            ]
        );
        StopWatch::stop(__METHOD__);
        return $schema;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getTypeLoaderClosure(TypeLoader $typeLoader): Closure
    {
        return function ($name) use ($typeLoader) {
            return $typeLoader->load($name);
        };
    }

    /**
     * @param Schema $schema
     * @param string $query
     * @param mixed[]|null $variables
     * @param int $index
     * @return mixed[]
     */
    protected function executeQuery(Schema $schema, string $query, ?array $variables, int $index): array
    {
        StopWatch::start(__METHOD__);

        $result = GraphQL::executeQuery($schema, $query, ['index' => $index], null, $variables)
            ->setErrorsHandler(ErrorHandler::getClosure())
            ->toArray(static::getDebugFlags());

        StopWatch::stop(__METHOD__);
        return $result;
    }

    public static function getDebugFlags(): int
    {
        if (Env::get('APP_ENV') === 'local') {
            return DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
        }
        return 0;
    }

    /**
     * @param mixed[] $response
     * @throws Throwable
     */
    protected function sendResponse(array $response): void
    {
        header('Content-Type: application/json');
        if (Env::get('APP_ENV') === 'local') {
            $response['_debugTimings'] = StopWatch::get();
        }
        try {
            echo json_encode($response, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            ErrorHandler::handleError($e);
            echo '{"errors":[[{"message":"Internal server error"}]]}';
        }
        $this->finishRequest();
    }

    /**
     * @codeCoverageIgnore
     */
    protected function finishRequest(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * @param mixed[] $args
     * @return bool
     */
    public static function runScript(array $args): bool
    {
        Env::init();
        $scriptName = strtolower(trim(array_shift($args)));
        foreach (ClassFinder::scripts() as $shortname => $classname) {
            if ($scriptName === strtolower($shortname)) {
                $script = new $classname();
                if ($script instanceof Script) {
                    try {
                        $script->run($args);
                        return true;
                    } catch (Throwable $e) {
                        ErrorHandler::sentryCapture($e);
                    }
                } else {
                    $e = new RuntimeException(
                        $classname . ' is not a script class. (Must extend Mrap\GraphCool\Definition\Script)'
                    );
                    ErrorHandler::sentryCapture($e);
                }
            }
        }
        return false;
    }

    public static function migrate(): void
    {
        Env::init();
        DB::migrate();
    }

    public static function onShutdown(Closure $closure): void
    {
        static::$shutdown[] = $closure;
    }

}