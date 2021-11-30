<?php

namespace Mrap\GraphCool\DataSource\Microservice;

use BadMethodCallException;
use GraphQL\Error\Error;
use GuzzleHttp\Client;
use Mrap\GraphCool\Utils\Env;
use RuntimeException;

/**
 * Class Microservice
 * @package GraphCool
 * @example
 *      try {
 *          /** @ var stdClass[] $result * /
 *          $result = Microservice::endpoint('crm:query:customers')
 *              ->authorization($_SERVER['HTTP_Authorization'])
 *              ->paramInt('id', 1)
 *              ->fields(['id','customer_number','salutation','first_name','last_name','email'])
 *              ->call();
 *      } catch (GraphqlException $e) {
 *          // failed to call microservice
 *      }
 */
class Microservice
{
    protected bool $debug = false;
    protected string $endpoint;
    protected string $query;
    protected ?array $variables;
    protected array $headers = [];
    protected array $params = [];
    protected array $fields = [];
    protected Client $client;
    protected bool $isRaw = false;

    protected function __construct(string $endpoint, Client $client = null)
    {
        $this->endpoint = $endpoint;
        if ($client === null) {
            $this->client = new Client();
        } else {
            $this->client = $client;
        }
    }

    public static function endpoint(string $endpoint, Client $client = null): Microservice
    {
        return new Microservice($endpoint, $client);
    }

    public function rawQuery(string $gql, ?array $variables = null): Microservice
    {
        $this->isRaw = true;
        $this->query = $gql;
        $this->variables = $variables;
        return $this;
    }

    public function authorization(string $bearer): Microservice
    {
        return $this->rawHeader('Authorization', $bearer);
    }

    public function rawHeader($key, $value): Microservice
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function paramString(string $name, ?string $value): Microservice
    {
        if ($value === null) {
            $this->paramNull($name);
            return $this;
        }
        $this->params[] = $name . ':"' . str_replace('"', '\\"', $value) . '"';
        return $this;
    }

    public function paramNull(string $name): Microservice
    {
        $this->params[] = $name . ':null';
        return $this;
    }

    public function paramInt(string $name, ?int $value): Microservice
    {
        if ($value === null) {
            $this->paramNull($name);
            return $this;
        }
        $this->params[] = $name . ':' . $value;
        return $this;
    }

    public function paramFloat(string $name, ?float $value): Microservice
    {
        if ($value === null) {
            $this->paramNull($name);
            return $this;
        }
        $this->params[] = $name . ':' . $value;
        return $this;
    }

    public function paramEnum(string $name, ?string $value): Microservice
    {
        if ($value === null) {
            $this->paramNull($name);
            return $this;
        }
        $this->params[] = $name . ':' . $value;
        return $this;
    }

    public function paramDate(string $name, ?string $value): Microservice
    {
        if ($value === null) {
            $this->paramNull($name);
            return $this;
        }
        $this->params[] = $name . ':"' . date('Y-m-d', strtotime($value)) . '"';
        return $this;
    }

    public function paramDateTime(string $name, ?string $value): Microservice
    {
        if ($value === null) {
            $this->paramNull($name);
            return $this;
        }
        $this->params[] = $name . ':"' . date('Y-m-d H:i:s', strtotime($value)) . '"';
        return $this;
    }

    public function paramTime(string $name, ?string $value): Microservice
    {
        if ($value === null) {
            $this->paramNull($name);
            return $this;
        }
        $this->params[] = $name . ':"' . date('H:i:s', strtotime($value)) . '"';
        return $this;
    }

    public function paramBool(string $name, bool $value): Microservice
    {
        $this->params[] = $name . ':' . ($value ? 'true' : 'false');
        return $this;
    }

    public function paramRaw(string $param): Microservice
    {
        $this->params[] = $param;
        return $this;
    }

    public function paramRawValue(string $name, $value): Microservice
    {
        $this->params[] = $name . ':' . $value;
        return $this;
    }

    public function fields(array $fields): Microservice
    {
        $this->fields = $fields;
        return $this;
    }

    public function addField(string $field): Microservice
    {
        $this->fields[] = $field;
        return $this;
    }

    public function call(): mixed
    {
        $client = $this->getClient();

        $response = $client->request('POST', $this->getUrl(), $this->getOptions());
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Got response status code ' . $response->getStatusCode() . ' from ' . $this->endpoint. ' Response was: ' . $response->getBody());
        }
        return $this->decodeBody($response->getBody());
    }

    protected function getClient(): Client
    {
        return $this->client;
    }

    protected function getUrl(): string
    {
        return $this->getBaseUrl() . '/' . $this->getServiceName() . '/graphql';
    }

    protected function getBaseUrl(): string
    {
        return match(Env::get('APP_ENV')) {
            //'local' => 'http://192.168.150.174:56201/',
            'local' => 'http://192.168.8.104:56201/',
            'production' => 'https://myhello.cloud',
            default => 'https://staging.myhello.cloud'
        };
    }

    protected function getServiceName(): string
    {
        return explode(':', $this->endpoint)[0];
    }

    protected function getOptions(): array
    {
        return [
            'headers' => $this->getHeaders(),
            'body'    => $this->getBody(),
            'timeout' => 2,
        ];
    }

    protected function getHeaders(): array
    {
        $headers = $this->headers;
        $headers['content-type'] = 'application/json; charset=utf8';
        return $headers;
    }

    protected function getBody(): string
    {
        $this->buildQuery();
        return json_encode(['query' => $this->query, 'variables' => $this->variables ?? []], JSON_THROW_ON_ERROR);
    }

    protected function buildQuery(): void
    {
        if (!empty($this->query)) {
            return;
        }
        $this->query = $this->getQueryType() . '{' . $this->getQueryName() . $this->getQueryParams(
            ) . $this->getQueryFields() . '}';
    }

    protected function getQueryType(): string
    {
        return explode(':', $this->endpoint)[1];
    }

    protected function getQueryName(): ?string
    {
        if ($this->isRaw) {
            return null;
        }
        return explode(':', $this->endpoint)[2] ?? $this->endpoint;
    }

    protected function getQueryParams(): string
    {
        if (empty($this->params)) {
            return '';
        }
        return '(' . implode(' ', $this->params) . ')';
    }

    protected function getQueryFields(): string
    {
        if (empty($this->fields)) {
            throw new BadMethodCallException(
                'Must use Microservice::fields() and set the fields to be queried, before using Microservice::call().'
            );
        }
        return $this->implodeFieldsRecursive($this->fields);
    }

    protected function implodeFieldsRecursive(array $fields): string
    {
        $data = [];
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $data[] = $key . $this->implodeFieldsRecursive($value);
            } else {
                $data[] = $value;
            }
        }
        return '{' . implode(' ', $data) . '}';
    }

    protected function decodeBody(string $body): mixed
    {
        $json = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        if (isset($json->errors)) {
            throw new Error($json->errors[0]->message ?? 'Internal error.');
        }
        if ($this->isRaw) {
            return $json->data;
        }
        $queryName = $this->getQueryName();
        return $json->data->$queryName;
    }


}
