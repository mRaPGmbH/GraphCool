<?php
declare(strict_types=1);

namespace Mrap\GraphCool;

use Exception;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use JsonException;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Model\Script;
use Mrap\GraphCool\Types\MutationType;
use Mrap\GraphCool\Types\QueryType;
use Mrap\GraphCool\Types\TypeLoader;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\StopWatch;
use RuntimeException;
use Throwable;

class GraphCool
{
    public static function run(): void
    {
        StopWatch::start(__METHOD__);
        $instance = new self();
        try {
            $request = $instance->parseRequest();
            $schema = $instance->createSchema();
            $result = $instance->executeQuery($schema, $request['query'], $request['variables'] ?? []);
        } catch (\Throwable $e) {
            $result = $instance->handleError($e);
        }
        StopWatch::stop(__METHOD__);
        $instance->sendResponse($result);
    }

    public static function runScript(array $args): void
    {
        $scriptName = strtolower(trim(array_shift($args)));
        foreach (ClassFinder::scripts() as $shortname => $classname) {
            if ($scriptName === strtolower($shortname)) {
                $script = new $classname();
                if ($script instanceof Script) {
                    $script->run($args);
                } else {
                    throw new RuntimeException($classname . ' is not a script class. (Must extend Mrap\GraphCool\Model\Script)');
                }
            } else {
                throw new RuntimeException($scriptName . ' is not a known script.');
            }
        }
    }

    public static function migrate(): void
    {
        DB::migrate();
    }

    /**
     * @return array
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
        StopWatch::stop(__METHOD__);
        return $request;
    }

    protected function createSchema(): Schema
    {
        StopWatch::start(__METHOD__);
        $typeLoader = new TypeLoader();
        $schema = new Schema(
            [
                'query'      => new QueryType($typeLoader),
                'mutation'   => new MutationType($typeLoader),
                'typeLoader' => function ($name) use ($typeLoader) {
                    return $typeLoader->load($name);
                }
            ]
        );
        StopWatch::stop(__METHOD__);
        return $schema;
    }

    protected function executeQuery(Schema $schema, string $query, ?array $variables): array
    {
        StopWatch::start(__METHOD__);
        $result = GraphQL::executeQuery($schema, $query, [], null, $variables)->toArray(
            DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE
        );
        StopWatch::stop(__METHOD__);
        return $result;
    }

    protected function handleError(Throwable $e): array
    {
        if ($e instanceof Error) {
            return [
                'errors' => [[
                    'message' => $e->getMessage(),
                    'extensions' => ['category' => 'graphql'],
                    'location' => ['line' => 1, 'column' => 1]
                ]]
            ];
        }

        $sentryDsn = Env::get('SENTRY_DSN');
        $environment = Env::get('APP_ENV');
        if ($sentryDsn !== null && $environment !== 'local' && function_exists("\Sentry\init")) {
            \Sentry\init([
                             'dsn' => $sentryDsn,
                             'environment' => Env::get('APP_ENV'),
                             'release' => Env::get('APP_NAME') . '@' . Env::get('APP_VERSION'),
                             'server_name' => $_SERVER['SERVER_NAME']
                         ]);
            \Sentry\captureException($e);
        }

        if ($environment === 'local') {
            return [
                'errors' => [
                    [
                        'message' => $e->getMessage(),
                        'debug_info'       => print_r($e, true),
                    ]
                ]
            ];
        }
        return [
            'errors' => [
                [
                    'message' => 'Internal error',
                ]
            ]
        ];
    }

    protected function sendResponse(array $response): void
    {
        header('Content-Type: application/json');
        $response['_debugTimings'] = StopWatch::get();
        try {
            echo json_encode($response, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->handleError($e);
            echo '{"errors":[[{"message":"Internal error"}]]}';
        }
    }

}