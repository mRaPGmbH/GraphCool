<?php

declare(strict_types=1);

namespace Mrap\GraphCool;

use Closure;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use JsonException;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\Script;
use Mrap\GraphCool\Types\MutationType;
use Mrap\GraphCool\Types\QueryType;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\ErrorHandler;
use Mrap\GraphCool\Utils\Exporter;
use Mrap\GraphCool\Utils\FileUpload;
use Mrap\GraphCool\Utils\Importer;
use Mrap\GraphCool\Utils\Scheduler;
use Mrap\GraphCool\Utils\StopWatch;
use RuntimeException;
use Throwable;

class GraphCool
{
    /** @var Closure[] */
    protected static array $shutdown = [];
    protected static Scheduler $scheduler;
    protected static Importer $importer;
    protected static Exporter $exporter;

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
                // must be $result and NOT $result[] - graphql-php manages multiqueries internally
                $result = $instance->executeQuery($schema, $query['query'], $query['variables'] ?? [], $index);
            }
        } catch (Throwable $e) {
            $result = ErrorHandler::handleError($e);
        }
        StopWatch::stop(__METHOD__);
        $instance->sendResponse($result);

        static::shutdown();
        StopWatch::log(json_encode($request ?? $result));
    }

    public static function reset(): void
    {
        static::$shutdown = [];
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
        $encoding = mb_detect_encoding($raw, mb_detect_order(), true);
        if ($encoding !== 'UTF-8') {
            $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
        }
        $raw = iconv('UTF-8', 'UTF-8', $raw);
        try {
            $request = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            StopWatch::stop(__METHOD__);
            throw new Error('Syntax Error: Unexpected <EOF>');
        }
        if (isset($_REQUEST['map'])) {
            $request = FileUpload::parse($request, $_REQUEST['map']);
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
        $schema = new Schema(
            [
                'query' => new QueryType(),
                'mutation' => new MutationType(),
                'typeLoader' => $this->getTypeLoaderClosure(),
            ]
        );
        StopWatch::stop(__METHOD__);
        return $schema;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getTypeLoaderClosure(): Closure
    {
        return function ($name) {
            return Type::get($name);
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
        $env = Env::get('APP_ENV');
        if ($env === 'local' || $env === 'test') {
            return DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
        }
        // @codeCoverageIgnoreStart
        return 0;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param mixed[] $response
     * @throws Throwable
     */
    protected function sendResponse(array $response): void
    {
        StopWatch::start(__METHOD__);
        header('Content-Type: application/json');
        $env = Env::get('APP_ENV');
        if ($env === 'local' || $env === 'test' || $env === 'staging') {
            $response['_debugTimings'] = StopWatch::get();
        }
        try {
            echo json_encode($response, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            ErrorHandler::handleError($e);
            echo '{"errors":[[{"message":"Internal server error"}]]}';
        }
        $this->finishRequest();
        StopWatch::stop(__METHOD__);
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
     * @return array
     */
    public static function runScript(array $args): array
    {
        $scriptName = strtolower(trim(array_shift($args)));
        $ret = [];
        try {
            $ret = match($scriptName) {
                'scheduler' => static::scheduler()->run(),
                'importer' => static::importer()->run(array_shift($args)),
                'exporter' => static::exporter()->run(array_shift($args)),
                default => static::executeScript($scriptName, $args)
            };
        } catch (Throwable $e) {
            ErrorHandler::sentryCapture($e);
            $ret = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
        static::shutdown();
        return $ret;
    }

    protected static function executeScript(string $scriptName, array $args): array
    {
        foreach (ClassFinder::scripts() as $shortname => $classname) {
            if ($scriptName === strtolower($shortname)) {
                try {
                    $script = new $classname();
                    if (!$script instanceof Script) {
                        throw new RuntimeException($classname . ' is not a script class. (Must extend Mrap\GraphCool\Definition\Script)');
                    }
                    $script->run($args);
                    return [
                        'success' => true,
                        'log' => $script->getLog()
                    ];
                } catch (Throwable $e) {
                    ErrorHandler::sentryCapture($e);
                    $result = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                    if (isset($script) && method_exists($script, 'getLog')) {
                        $result['log'] = $script->getLog();
                    }
                    return $result;
                }
            }
        }
        throw new RuntimeException('Script ' . $scriptName . ' not found.');
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

    protected static function shutdown(): void
    {
        StopWatch::start(__METHOD__);
        foreach (static::$shutdown as $closure) {
            try {
                $closure();
            } catch (Throwable $e) {
                ErrorHandler::sentryCapture($e);
            }
        }
        StopWatch::stop(__METHOD__);
    }

    protected static function scheduler(): Scheduler
    {
        if (!isset(static::$scheduler)) {
            static::$scheduler = new Scheduler();
        }
        return static::$scheduler;
    }

    public static function setScheduler(Scheduler $scheduler): void
    {
        static::$scheduler = $scheduler;
    }

    protected static function importer(): Importer
    {
        if (!isset(static::$importer)) {
            static::$importer = new Importer();
        }
        return static::$importer;
    }

    public static function setImporter(Importer $importer): void
    {
        static::$importer = $importer;
    }

    protected static function exporter(): Exporter
    {
        if (!isset(static::$exporter)) {
            static::$exporter = new Exporter();
        }
        return static::$exporter;
    }

    public static function setExporter(Exporter $exporter): void
    {
        static::$exporter = $exporter;
    }

}
