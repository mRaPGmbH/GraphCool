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
use Throwable;

class GraphCool
{
    public static function run(): void
    {
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
        $raw = file_get_contents('php://input');
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function createSchema(): Schema
    {
        $typeLoader = new TypeLoader();
        return new Schema(
            [
                'query'      => new QueryType($typeLoader),
                'mutation'   => new MutationType($typeLoader),
                'typeLoader' => function ($name) use ($typeLoader) {
                    return $typeLoader->load($name);
                }
            ]
        );
    }

    protected function executeQuery(Schema $schema, string $query, ?array $variables): array
    {
        return GraphQL::executeQuery($schema, $query, [], null, $variables)->toArray(
            DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE
        );
    }

    protected function sendResponse(array $result): void
    {
        header('Content-Type: application/json');
        try {
            echo json_encode($result, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo '{"errors":[{"message":"Internal error"}]}';
        }
    }

}