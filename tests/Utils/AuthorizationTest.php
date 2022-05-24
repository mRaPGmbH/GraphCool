<?php


namespace Mrap\GraphCool\Tests\Utils;


use GraphQL\Error\Error;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\Authorization;

class AuthorizationTest extends TestCase
{
    public function testAuthorizeError():void
    {
        $this->expectException(Error::class);
        $this->provideJwt();
        Authorization::checkPermissions('write', 'model', 'crm:model.1');
    }

}
