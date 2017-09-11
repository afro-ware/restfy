<?php

namespace Afroware\Restfy\Transformer;

use Closure;
use RuntimeException;
use Afroware\Restfy\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Afroware\Restfy\Contract\Transformer\Adapter;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request as IlluminateRequest;

class Factory
{
    /**
     * Illuminate container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Array of registered transformer bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Transformation layer adapter being used to transform responses.
     *
     * @var \Afroware\Restfy\Contract\Transformer\Adapter
     */
    protected $adapter;

    /**
     * Create a new transformer factory instance.
     *
     * @param \Illuminate\Container\Container         $container
     * @param \Afroware\Restfy\Contract\Transformer\Adapter $adapter
     *
     * @return void
     */
    public function __construct(Container $container, Adapter $adapter)
    {
        $this->container = $container;
        $this->adapter = $adapter;
    }

    /**
     * Register a transformer binding resolver for a class.
     *
     * @param string                 $class
     * @param string|callable|object $resolver
     * @param array|\Closure         $third
     * @param \Closure               $fourth
     *
     * @return \Afroware\Restfy\Transformer\Binding
     */
    public function register($class, $resolver, $third = null, $fourth = null)
    {
        if (func_num_args() == 4) {
            list($parameters, $after) = array_slice(func_get_args(), 2);
        } elseif (is_array($third)) {
            list($parameters, $after) = [$third, null];
        } elseif ($third instanceof Closure) {
            list($parameters, $after) = [[], $third];
        } else {
            list($parameters, $after) = [[], null];
        }

        return $this->bindings[$class] = $this->createBinding($resolver, $parameters, $after);
    }

    /**
     * Transform a response.
     *
     * @param string|object $response
     *
     * @return mixed
     */
    public function transform($response)
    {
        $binding = $this->getBinding($response);

        return $this->adapter->transform($response, $binding->resolveTransformer(), $binding, $this->getRequest());
    }

    /**
     * Determine if a response is transformable.
     *
     * @param mixed $response
     *
     * @return bool
     */
    public function transformableResponse($response)
    {
        return $this->transformableType($response) && $this->hasBinding($response);
    }

    /**
     * Determine if a value is of a transformable type.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function transformableType($value)
    {
        return is_object($value) || is_string($value);
    }

    /**
     * Get a registered transformer binding.
     *
     * @param string|object $class
     *
     * @throws \RuntimeException
     *
     * @return \Afroware\Restfy\Transformer\Binding
     */
    protected function getBinding($class)
    {
        if ($this->isCollection($class) && ! $class->isEmpty()) {
            return $this->getBindingFromCollection($class);
        }

        $class = is_object($class) ? get_class($class) : $class;

        if (! $this->hasBinding($class)) {
            throw new RuntimeException('Unable to find bound transformer for "'.$class.'" class.');
        }

        return $this->bindings[$class];
    }

    /**
     * Create a new binding instance.
     *
     * @param string|callable|object $resolver
     * @param array                  $parameters
     * @param \Closure               $callback
     *
     * @return \Afroware\Restfy\Transformer\Binding
     */
    protected function createBinding($resolver, array $parameters = [], Closure $callback = null)
    {
        return new Binding($this->container, $resolver, $parameters, $callback);
    }

    /**
     * Get a registered transformer binding from a collection of items.
     *
     * @param \Illuminate\Support\Collection $collection
     *
     * @return null|string|callable
     */
    protected function getBindingFromCollection($collection)
    {
        return $this->getBinding($collection->first());
    }

    /**
     * Determine if a class has a transformer binding.
     *
     * @param string|object $class
     *
     * @return bool
     */
    protected function hasBinding($class)
    {
        if ($this->isCollection($class) && ! $class->isEmpty()) {
            $class = $class->first();
        }

        $class = is_object($class) ? get_class($class) : $class;

        return isset($this->bindings[$class]);
    }

    /**
     * Determine if the instance is a collection.
     *
     * @param object $instance
     *
     * @return bool
     */
    protected function isCollection($instance)
    {
        return $instance instanceof Collection || $instance instanceof Paginator;
    }

    /**
     * Get the array of registered transformer bindings.
     *
     * @return array
     */
    public function getTransformerBindings()
    {
        return $this->bindings;
    }

    /**
     * Set the transformation layer at runtime.
     *
     * @param \Closure|\Afroware\Restfy\Contract\Transformer\Adapter $adapter
     *
     * @return void
     */
    public function setAdapter($adapter)
    {
        if (is_callable($adapter)) {
            $adapter = call_user_func($adapter, $this->container);
        }

        $this->adapter = $adapter;
    }

    /**
     * Get the transformation layer adapter.
     *
     * @return \Afroware\Restfy\Contract\Transformer\Adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Get the request from the container.
     *
     * @return \Afroware\Restfy\Http\Request
     */
    public function getRequest()
    {
        $request = $this->container['request'];

        if ($request instanceof IlluminateRequest && ! $request instanceof Request) {
            $request = (new Request())->createFromIlluminate($request);
        }

        return $request;
    }

    /**
     * Pass unknown method calls through to the adapter.
     *
     * @param string $method
     * @Param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->adapter, $method], $parameters);
    }
}
