<?php

namespace Pagekit\Component\Routing\Controller;

use Pagekit\Component\Routing\Exception\LoaderException;
use Pagekit\Component\Routing\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ControllerCollection implements ControllerResolverInterface
{
    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * @var ControllerResolverInterface
     */
    protected $resolver;

    /**
     * @var RouteCollection
     */
    protected $collection;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var array
     */
    protected $callbacks = [];

    /**
     * @var array
     */
    protected $controllers = [];

    /**
     * Constructor.
     *
     * @param LoaderInterface             $loader
     * @param ControllerResolverInterface $resolver
     */
    public function __construct(LoaderInterface $loader, ControllerResolverInterface $resolver)
    {
        $this->loader   = $loader;
        $this->resolver = $resolver;
        $this->routes   = new RouteCollection;
    }

    /**
     * Maps a GET request to a callable.
     *
     * @param  string $path
     * @param  string $name
     * @param  mixed  $callback
     * @return Route
     */
    public function get($path, $name, $callback)
    {
        $route = $this->map($path, $name, $callback);
        $route->setMethods('GET');

        return $route;
    }

    /**
     * Maps a POST request to a callable.
     *
     * @param  string $path
     * @param  string $name
     * @param  mixed  $callback
     * @return Route
     */
    public function post($path, $name, $callback)
    {
        $route = $this->map($path, $name, $callback);
        $route->setMethods('POST');

        return $route;
    }

    /**
     * Maps a path to a callable.
     *
     * @param  string $path
     * @param  string $name
     * @param  mixed  $callback
     * @return Route
     */
    public function map($path, $name, $callback)
    {
        $route = new Route($path);
        $route->setDefault('_controller', '::'.$name);

        $this->routes->add($name, $route);
        $this->callbacks[$name] = $callback;

        return $route;
    }

    /**
     * Adds a controller.
     *
     * @param string $controller
     * @param array  $options
     */
    public function add($controller, array $options = [])
    {
        $this->controllers[$controller] = $options;
    }

    /**
     * Gets the route collection.
     *
     * @return RouteCollection
     */
    public function getRoutes()
    {
        if (!$this->collection) {

            $this->collection = new RouteCollection;

            foreach ($this->controllers as $controller => $options) {
                try {
                    $this->collection->addCollection($this->loader->load($controller, $options));
                } catch (LoaderException $e) {}
            }

            $this->collection->addCollection($this->routes);
        }

        return $this->collection;
    }

    /**
     * Returns an array of resources of this collection.
     *
     * @return array
     */
    public function getResources()
    {
        $callbacks = [];
        $controllers = array_keys($this->controllers);

        foreach ($this->routes as $name => $route) {
            $callbacks[] = $name.$route->getPath();
        }

        return compact('callbacks', 'controllers');
    }

    /**
     * {@inheritdoc}
     */
    public function getController(Request $request)
    {
        $controller = $request->attributes->get('_controller', '');

        if (0 === strpos($controller, '::') && $name = substr($controller, 2) and isset($this->callbacks[$name])) {
            return $this->callbacks[$name];
        }

        return $this->resolver->getController($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(Request $request, $controller)
    {
        return $this->resolver->getArguments($request, $controller);
    }
}
