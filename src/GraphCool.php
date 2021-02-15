<?php

namespace Mrap\GraphCool;

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use JsonException;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Types\MutationType;
use Mrap\GraphCool\Types\QueryType;
use Mrap\GraphCool\Types\TypeLoader;
use Mrap\GraphCool\Utils\StopWatch;
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
            $result = $instance->executeQuery($schema, $request['query'], $request['variables']);
        } catch (Throwable $e) {
            $result = [
                'errors' => [
                    'message' => $e->getMessage(),
                    'e'       => print_r($e, true),
                ]
            ];
        }
        StopWatch::stop(__METHOD__);
        $instance->sendResponse($result);
    }

    public static function migrate(): void
    {
        DB::migrate();
    }

    /**
     * @return array
     * @throws JsonException
     */
    protected function parseRequest(): array
    {
        StopWatch::start(__METHOD__);
        $raw = file_get_contents('php://input');
        StopWatch::stop(__METHOD__);
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
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

    protected function sendResponse(array $response): void
    {
        header('Content-Type: application/json');
        $response['_debugTimings'] = StopWatch::get();
        try {
            echo json_encode($response, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo '{"errors":[{"message":"Internal error"}]}';
        }
    }

}