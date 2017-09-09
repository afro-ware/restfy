<?php

namespace Afroware\Restfy\Tests\Routing\Adapter;

use Illuminate\Http\Request;
use Laravel\Lumen\Application;
use Afroware\Restfy\Routing\Adapter\Lumen;
use FastRoute\RouteParser\Std as StdRouteParser;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;

class LumenTest extends BaseAdapterTest
{
    public function getAdapterInstance()
    {
        $this->container->routeMiddleware([
            'restfy.auth' => get_class($this->container['restfy.auth']),
            'restfy.limiting' => get_class($this->container['restfy.limiting']),
        ]);

        // When we rebind the "request" instance during testing we'll pull the route resolver
        // from the Lumen request instance and set it on our request so we can fetch
        // the route properly.
        $this->container->rebinding('request', function ($app, $request) {
            $request->setRouteResolver($app[Request::class]->getRouteResolver());
        });

        return new Lumen($this->container, new StdRouteParser, new GcbDataGenerator, GcbDispatcher::class);
    }

    public function getContainerInstance()
    {
        return new Application;
    }

    public function testRoutesWithDomains()
    {
        $this->markTestSkipped('Lumen does not support sub-domain routing.');
    }
}
