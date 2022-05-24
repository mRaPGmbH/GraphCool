<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use DateTimeImmutable;
use GraphQL\Error\Error;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Ramsey\Uuid\Uuid;

class JwtAuthentication
{

    public const GUEST = 0;
    public const USER_READONLY = 1;
    public const USER = 2;
    public const ADMIN = 3;
    public const SUPER_ADMIN = 4;

    /** @var mixed[] */
    protected static array $claims;

    public static function authenticate(): void
    {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            throw new Error('Authorization header is missing in request.', null, null, [], null, null, ['category' => 'authorization']);
        }
        if (!str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ')) {
            throw new Error('Authorization header in request does not appear to be a JWT.', null, null, [], null, null, ['category' => 'authorization']);
        }
        $jwt = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
        $payload = base64_decode(explode('.', $jwt)[1]??'');
        if (strpos($payload, '"iss":"' . Env::get('APP_NAME') . '"') > 0) {
            $config = static::localConfig();
        } else {
            $config = static::centralConfig();
        }
        try {
            $token = $config->parser()->parse($jwt);
        } catch (InvalidTokenStructure $e) {
            throw new Error($e->getMessage(), null, null, [], null, null, ['category' => 'authorization']);
        } catch (CannotDecodeContent $e) {
            throw new Error($e->getMessage(), null, null, [], null, null, ['category' => 'authorization']);
        }

        $constraints = [];
        $constraints[] = new SignedWith($config->signer(), $config->verificationKey());
        $constraints[] = new LooseValidAt(new FrozenClock(new DateTimeImmutable()));

        if (!$config->validator()->validate($token, ...$constraints)) {
            throw new Error('JWT token could not be validated.', null, null, [], null, null, ['category' => 'authorization']);
        }

        /*
        try {
            $config->validator()->assert($token, ...$constraints);
        } catch (RequiredConstraintsViolated $e) {
            // list of constraints violation exceptions:
            var_dump($e->violations());
        }*/

        static::$claims = $token->claims()->all();
        if (!isset(static::$claims['tid'])) {
            throw new Error('Tenant ID (tid) is missing from JWT.', null, null, [], null, null, ['category' => 'authorization']);
        }
        static::$claims['tid'] = (string)static::$claims['tid'];
    }

    protected static function centralConfig(): Configuration
    {
        return Configuration::forAsymmetricSigner(
            new Signer\Rsa\Sha256(),
            InMemory::empty(),
            InMemory::file(ClassFinder::rootPath() . '/jwtkey-public.pem')
        );
    }

    protected static function localConfig(): Configuration
    {
        $secret = Env::get('JWT_SECRET');
        if ($secret === null) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('JWT_SECRET is missing from .env');
            // @codeCoverageIgnoreEnd
        }
        return Configuration::forSymmetricSigner(
            new Signer\Hmac\Sha256(),
            InMemory::plainText($secret)
        );
    }

    public static function createLocalToken(array $permissions, string $tenantId): string
    {
        $now = new DateTimeImmutable();
        $config = static::localConfig();
        return $config->builder()
            ->issuedBy(Env::get('APP_NAME'))
            ->identifiedBy(Uuid::uuid4()->toString())
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('tid', $tenantId)
            ->withClaim('perm', Permissions::createLocalCode($permissions))
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }


    public static function tenantId(): ?string
    {
        return static::$claims['tid'] ?? null;
    }

    public static function overrideTenantId(string $id): void
    {
        static::$claims['tid'] = $id;
    }

    public static function getClaim(string $key): mixed
    {
        return static::$claims[$key] ?? null;
    }

}
