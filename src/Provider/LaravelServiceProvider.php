<?php

namespace Afroware\Restfy\Provider;

use ReflectionClass;
use Afroware\Restfy\Http\Middleware\Auth;
use Illuminate\Contracts\Http\Kernel;
use Afroware\Restfy\Event\RequestWasMatched;
use Afroware\Restfy\Http\Middleware\Request;
use Afroware\Restfy\Http\Middleware\RateLimit;
use Illuminate\Routing\ControllerDispatcher;
use Afroware\Restfy\Http\Middleware\PrepareController;
use Afroware\Restfy\Routing\Adapter\Laravel as LaravelAdapter;

class LaravelServiceProvider extends RestfyServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->publishes([realpath(__DIR__.'/../../config/restfy.php') => config_path('restfy.php')]);

        $kernel = $this->app->make(Kernel::class);

        $this->app[Request::class]->mergeMiddlewares(
            $this->gatherAppMiddleware($kernel)
        );

        $this->addRequestMiddlewareToBeginning($kernel);

        $this->app['events']->listen(RequestWasMatched::class, function (RequestWasMatched $event) {
            $this->replaceRouteDispatcher();

            $this->updateRouterBindings();
        });

        $this->addMiddlewareAlias('restfy.auth', Auth::class);
        $this->addMiddlewareAlias('restfy.throttle', RateLimit::class);
        $this->addMiddlewareAlias('restfy.controllers', PrepareController::class);
    }

    /**
     * Replace the route dispatcher.
     *
     * @return void
     */
    protected function replaceRouteDispatcher()
    {
        $this->app->singleton('illuminate.route.dispatcher', function ($app) {
            return new ControllerDispatcher($app['restfy.router.adapter']->getRouter(), $app);
        });
    }

    /**
     * Grab the bindings from the Laravel router and set them on the adapters
     * router.
     *
     * @return void
     */
    protected function updateRouterBindings()
    {
        foreach ($this->getRouterBindings() as $key => $binding) {
            $this->app['restfy.router.adapter']->getRouter()->bind($key, $binding);
        }
    }

    /**
     * Get the Laravel routers bindings.
     *
     * @return array
     */
    protected function getRouterBindings()
    {
        $property = (new ReflectionClass($this->app['router']))->getProperty('binders');
        $property->setAccessible(true);

        return $property->getValue($this->app['router']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerRouterAdapter();
    }

    /**
     * Register the router adapter.
     *
     * @return void
     */
    protected function registerRouterAdapter()
    {
        $this->app->singleton('restfy.router.adapter', function ($app) {
            return new LaravelAdapter($app['router']);
        });
    }

    /**
     * Add the request middleware to the beginning of the kernel.
     *
     * @param \Illuminate\Contracts\Http\Kernel $kernel
     *
     * @return void
     */
    protected function addRequestMiddlewareToBeginning(Kernel $kernel)
    {
        $kernel->prependMiddleware(Request::class);
    }

    /**
     * Register a short-hand name for a middleware. For Compatability
     * with Laravel < 5.4 check if aliasMiddleware exists since this
     * method has been renamed.
     *
     * @param string $name
     * @param string $class
     *
     * @return void
     */
    protected function addMiddlewareAlias($name, $class)
    {
        $router = $this->app['router'];

        if (method_exists($router, 'aliasMiddleware')) {
            return $router->aliasMiddleware($name, $class);
        }

        return $router->middleware($name, $class);
    }

    /**
     * Gather the application middleware besides this one so that we can send
     * our request through them, exactly how the developer wanted.
     *
     * @param \Illuminate\Contracts\Http\Kernel $kernel
     *
     * @return array
     */
    protected function gatherAppMiddleware(Kernel $kernel)
    {
        $property = (new ReflectionClass($kernel))->getProperty('middleware');
        $property->setAccessible(true);

        return $property->getValue($kernel);
    }
}
