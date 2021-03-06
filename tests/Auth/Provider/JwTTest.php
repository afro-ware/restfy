<?php

namespace Afroware\Restfy\Tests\Auth\Provider;

use Mockery as m;
use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Afroware\Restfy\Auth\Provider\JwT;
use Afroware\JwTauth\Exceptions\JwTException;

class JwTTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->auth = m::mock('Afroware\JwTauth\JwTauth');
        $this->provider = new JwT($this->auth);
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testValidatingAuthorizationHeaderFailsAndThrowsException()
    {
        $request = Request::create('foo', 'GET');
        $this->provider->authenticate($request, m::mock('Afroware\Restfy\Routing\Route'));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testAuthenticatingFailsAndThrowsException()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andThrow(new JwTException('foo'));

        $this->provider->authenticate($request, m::mock('Afroware\Restfy\Routing\Route'));
    }

    public function testAuthenticatingSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andReturn((object) ['id' => 1]);

        $this->assertSame(1, $this->provider->authenticate($request, m::mock('Afroware\Restfy\Routing\Route'))->id);
    }

    public function testAuthenticatingWithQueryStringSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET', ['token' => 'foo']);

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andReturn((object) ['id' => 1]);

        $this->assertSame(1, $this->provider->authenticate($request, m::mock('Afroware\Restfy\Routing\Route'))->id);
    }
}
