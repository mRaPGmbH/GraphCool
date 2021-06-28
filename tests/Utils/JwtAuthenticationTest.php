<?php


namespace Mrap\GraphCool\Tests\Utils;


use GraphQL\Error\Error;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{

    public function testAuthenticate(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOjEyMywiYXVkIjoiZm9vIiwidGlkIjoxLCJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODA4MCIsImlhdCI6MTU5MzUwOTYxNSwiZXhwIjoxOTAxMDkzNjE1LCJuYmYiOjE1OTM1MDk2MTUsImp0aSI6Inh4MHFYNzMydTVSdGFBNDkifQ.KNOhgi8OzGNrWXT0T0a66Ifk1AX-q2PFGo6YEskz9aHrO4yepK5HmxHyYval6RxjvV22z4p4r4Z_h1EtSUJHovZviWBzXgiOxQXAUlnBWJebpl256D5u0b7JDx2mOR6VZuu6nCpEGr6lq38VuW_yiVyJLhTdvfLVzF6rEFsnI54jBUlK1k5zmPDImzBJUoPa-BvAgOwLUfvDdiudsMs-a3tiZ5me7JmRaktPq6s_dGGjWVzeVAYD8rfs-WlHUJg0DkNbQWN9iPdnChryopwE7KjWZBKQPSH8RNuWd_eC0FQN97mcfPIAs_FBqiOQP0C8p1_2bvw8VpcGBp88DDPlZg';
        JwtAuthentication::authenticate();
        self::assertEquals(1, JwtAuthentication::tenantId());

        JwtAuthentication::overrideTenantId(2);
        self::assertEquals(2, JwtAuthentication::tenantId());
    }

    public function testAuthenticateErrorHeaderMissing(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $this->expectException(Error::class);
        JwtAuthentication::authenticate();
    }

    public function testAuthenticateErrorBearerMissing(): void
    {
        $this->expectException(Error::class);
        $_SERVER['HTTP_AUTHORIZATION'] = 'asdfasdf';
        JwtAuthentication::authenticate();
    }

    public function testAuthenticateErrorTokenMalformed(): void
    {
        $this->expectException(Error::class);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer asdfasdf';
        JwtAuthentication::authenticate();
    }

    public function testAuthenticateErrorTokenMalformed2(): void
    {
        $this->expectException(Error::class);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOjEyMywiYXVkIjoiZm9vIiwidGlkIjoxLCJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODA4MCIsImlhdCI6MTU5MzUwOTYxNSwiZXhwIjoxOTAxMDkzNjE1LCJuYmYiOjE1OTM1MDk2MTUsImp0aSI6Inh4MHFYNzMydTVSdGFBNDkifQ.KNOhgi8OzGNrWXT0T0a66Ifk1AX-q2PFGo6YEskz9aHrO4yepK5HmxHyYval6RxjvV22z4p4r4Z_h1EtSUJHovZviWBzXgiOxQXAUlnBWJebpl256D5u0b7JDx2mOR6VZuu6nCpEGr6lq38VuW_yiVyJLhTdvfLVzF6rEFsnI54jBUlK1k5zmPDImzBJUoPa-BvAgOwLUfvDdiudsMs-a3tiZ5me7JmRaktPq6s_dGGjWVzeVAYD8rfs-WlHUJg0DkNbQWN9iPdnChryopwE7KjWZBKQPSH8RNuWd_eC0FQN97mcfPIAs_FBqiOQP0C8p1_2bvw8VpcGBp88DDPlZx';
        JwtAuthentication::authenticate();
    }

    public function testInvalidToken(): void
    {
        $this->expectException(Error::class);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MjQ4ODYzMjQuMDkwODM3LCJleHAiOjI1NzE1NzExMjQuMDkwODM3LCJ0aWQiOjF9.dexMcyWV22KCxk8DJHZ0ZGwoBN7oW2v8dlgaScsmyDCpKaXu8b1w4IBO2qGhZqj6Bo-v9bdh2g_iMENxYZTx95AqpFD4tzWEjpfIBhC1z9DoI0BE_UlzBnxLXDGiIiwpTX_Pwa5nnlqprMjH3heneaGkSS5jVyDljnsNtERBM1J2yRyxWeWzgICp1czHwLqnWiW5vlnUSq7j4rVDQ9td9hIocd3o0yEz-wR5vPO-dUQLtCH8eXDAHTRCaI3oDGoDr4lapMOcpEhk3SSXobyG8shSJNx544vBReT-4NnPf_Ev4-SmlCAfRwZo0IK6eKhePfVgb1yBmBjgrOgDborBhw';
        JwtAuthentication::authenticate();
    }

    public function testMissingTenantId(): void
    {
        $this->expectException(Error::class);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MjQ4ODYxMDUuMjg5OTQ1LCJleHAiOjI1NzE1NzA5MDUuMjg5OTQ1fQ.LtumCldTkBQEWp3ziE-ElCJgk3gGCf4VyyitMY0DllBpelS69L9LtkwkvGxGqH3XAM8JC4LJsEaSWrqYdQmPv1Y4rQhRfeIs2fCLTQ9MqPvBn7rt4J76OK0CF1luUhspvipJpTLr8tN00Gjm9vCDoTqJXTTBfQX3hs6RtKAB_IB1ynf5p4iJjGsFHyOJ4V4hzaY1wqBc7fmQ51CJt0BFzc3HJAk3J4mo7vEt66YbKXa_Rp3R_Ve2kZtVMCrxMykZ9Pz12-nc-2GBb1tga--5FhO1fbq_Az92KJtUpSSomscN-aYjzFkVkL3aTjylY6Zszsr1kbjgQ9_XqKs9GtkTpQ';
        JwtAuthentication::authenticate();
    }


    private function token(): void
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            LocalFileReference::file($this->dataPath() . '/jwtkey-private.pem', '3SfSffuusva85xGPcP99GitNw00QBP8s9JDLTb3vtT2F4kcZRPrBi5SaTBeY018v'),
            //LocalFileReference::file($this->dataPath() . '/otherkey-private.pem', 'asdfghjklöä'),
            InMemory::empty(),
        );
        $now = new \DateTimeImmutable();
        $token = $config->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+30 years'))
            //->withClaim('tid', 1)
            ->getToken($config->signer(), $config->signingKey());
        var_dump($token->toString());
    }
}