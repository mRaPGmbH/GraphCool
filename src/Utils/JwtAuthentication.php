<?php


namespace Mrap\GraphCool\Utils;

use GraphQL\Error\Error;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class JwtAuthentication
{

    public const GUEST = 0;
    public const USER_READONLY = 1;
    public const USER = 2;
    public const ADMIN = 3;
    public const SUPER_ADMIN = 4;

    protected static array $claims;

    public static function authenticate()
    {
        $config = Configuration::forAsymmetricSigner(
            new Signer\Rsa\Sha256(),
            InMemory::empty(),
            LocalFileReference::file(ClassFinder::rootPath() . '/jwtkey-public.pem')
        );

        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            throw new Error('Authorization header is missing in request.');
        }
        if (!str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ')) {
            throw new Error('Authorization header in request does not appear to be a JWT.');
        }

        try {
            $token = $config->parser()->parse(substr($_SERVER['HTTP_AUTHORIZATION'], 7));
        } catch(InvalidTokenStructure $e) {
            throw new Error($e->getMessage());
        } catch(CannotDecodeContent $e) {
            throw new Error($e->getMessage());
        }

        $constraints = [];
        $constraints[] = new SignedWith($config->signer(), $config->verificationKey());

        if (! $config->validator()->validate($token, ...$constraints)) {
            throw new Error('JWT token could not be validated.');
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
            throw new Error('Tenant ID (tid) is missing from JWT.');
        }
        static::$claims['tid'] = (string) static::$claims['tid'];
    }

    public static function tenantId(): ?string
    {
        return static::$claims['tid'] ?? null;
    }

    public static function overrideTenantId(string $id): void
    {
        static::$claims['tid'] = $id;
    }


}