<?php

namespace Afroware\Restfy\Tests\Auth\Provider;

use Mockery as m;
use Afroware\Restfy\Routing\Route;
use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Afroware\Restfy\Tests\Stubs\AuthorizationProviderStub;

class AuthorizationTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testExceptionThrownWhenAuthorizationHeaderIsInvalid()
    {
        $request = Request::create('GET', '/');

        (new AuthorizationProviderStub)->authenticate($request, m::mock(Route::class));
    }
}
