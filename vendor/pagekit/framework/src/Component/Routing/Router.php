<?php

namespace Pagekit\Component\Routing;

use Pagekit\Component\Routing\Controller\ControllerCollection;
use Pagekit\Component\Routing\Generator\UrlGeneratorDumper;
use Pagekit\Component\Routing\Generator\UrlGeneratorInterface;
use Pagekit\Component\Routing\RequestContext as ExtendedRequestContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class Router implements RouterInterface, UrlGeneratorInterface
{
    /**
     * @var HttpKernelInterface
     */
    protected $kernel;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var UrlMatcher
     */
    protected $matcher;

    /**
     * @var UrlGenerator
     */
    protected $generator;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var ControllerCollection
     */
    protected $controllers;

    /**
     * @var array
     */
    protected $aliases;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var array
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param HttpKernelInterface  $kernel
     * @param ControllerCollection $controllers
     * @param array                $options
     */
    public function __construct(HttpKernelInterface $kernel, ControllerCollection $controllers, array $options = [])
    {
        $this->kernel      = $kernel;
        $this->controllers = $controllers;
        $this->aliases     = [];
        $this->context     = new ExtendedRequestContext;

        $this->options = array_replace([
            'cache'     => null,
            'matcher'   => 'Symfony\Component\Routing\Matcher\UrlMatcher',
            'generator' => 'Pagekit\Component\Routing\Generator\UrlGenerator'
        ], $options);
    }

    /**
     * Get the current request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * Gets a route by name.
     *
     * @param string $name The route name
     *
     * @return Route|null A Route instance or null when not found
     */
    public function getRoute($name)
    {
        return $this->getRouteCollection()->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        if (!$this->routes) {

            $this->routes = $this->controllers->getRoutes();

            foreach ($this->aliases as $source => $alias) {

                $name = $source;
                $params = [];

                if ($query = substr(strstr($source, '?'), 1)) {
                    $name = strstr($source, '?', true);
                    parse_str($query, $params);
                }

                if ($route = $this->routes->get($name)) {
                    $this->routes->add($source, new Route($alias[0], array_merge($route->getDefaults(), $params, ['_variables' => $route->compile()->getPathVariables()])));
                }
            }
        }

        return $this->routes;
    }

    /**
     * Gets the URL matcher instance.
     *
     * @return UrlMatcher
     */
    public function getMatcher()
    {
        if (!$this->matcher) {
            if ($cache = $this->getCache('%s/%s.matcher.cache')) {

                $class = sprintf('UrlMatcher%s', $cache['key']);

                if (!$cache['fresh']) {
                    $options = ['class' => $class, 'base_class' => $this->options['matcher']];
                    $this->writeCache($cache['file'], (new PhpMatcherDumper($this->getRouteCollection()))->dump($options));
                }

                require_once($cache['file']);

                $this->matcher = new $class($this->context);

            } else {

                $class = $this->options['matcher'];

                $this->matcher = new $class($this->getRouteCollection(), $this->context);
            }
        }

        return $this->matcher;
    }

    /**
     * Gets the UrlGenerator instance associated with this Router.
     *
     * @return UrlGenerator
     */
    public function getGenerator()
    {
        if (!$this->generator) {
            if ($cache = $this->getCache('%s/%s.generator.cache')) {

                $class = sprintf('UrlGenerator%s', $cache['key']);

                if (!$cache['fresh']) {
                    $options = ['class' => $class, 'base_class' => $this->options['generator']];
                    $this->writeCache($cache['file'], (new UrlGeneratorDumper($this->getRouteCollection()))->dump($options));
                }

                require_once($cache['file']);

                $this->generator = new $class($this->context);

            } else {

                $class = $this->options['generator'];

                $this->generator = new $class($this->getRouteCollection(), $this->context);
            }
        }

        return $this->generator;
    }

    /**
     * Gets an alias.
     *
     * @param  string $name
     * @return array
     */
    public function getAlias($name)
    {
        return isset($this->aliases[$name]) || isset($this->aliases[$name = strtok($name, '?')]) ? $this->aliases[$name] : false;
    }

    /**
     * Adds an alias.
     *
     * @param string   $path
     * @param string   $name
     * @param callable $inbound
     * @param callable $outbound
     */
    public function addAlias($path, $name, callable $inbound = null, callable $outbound = null)
    {
        $path = preg_replace('/^[^\/]/', '/$0', $path);

        $this->aliases[$name] = [$path, $inbound, $outbound];
    }

    /**
     * Aborts the current request by sending a proper HTTP error.
     *
     * @param int $code
     * @param string $message
     * @param array $headers
     * @throws HttpException
     * @throws NotFoundHttpException
     */
    public function abort($code, $message = '', array $headers = [])
    {
        if ($code == 404) {
            throw new NotFoundHttpException($message);
        } else {
            throw new HttpException($code, $message, null, $headers);
        }
    }

    /**
     * Terminates a request/response cycle.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response)
    {
        $this->kernel->terminate($request, $response);
    }

    /**
     * Handles a Request to convert it to a Response.
     *
     * @param  Request $request
     * @param  int     $type
     * @param  bool    $catch
     * @return Response
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $this->request = $request;
        $this->context->fromRequest($request);

        return $this->kernel->handle($request, $type, $catch);
    }

    /**
     * Handles a Subrequest to call an action internally.
     *
     * @param  string $name
     * @param  array  $parameters
     * @throws \RuntimeException
     * @return Response
     */
    public function call($name, $parameters = [])
    {
        if (empty($this->request)) {
            throw new \RuntimeException('No Request set.');
        }

        return $this->kernel->handle(Request::create($this->generate($name, $parameters, true)), HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        $params = $this->getMatcher()->match($pathinfo);

        if ($alias = $this->getAlias($params['_route']) and is_callable($alias[1])) {
            $params = call_user_func($alias[1], $params);
        }

        if (false !== $pos = strpos($params['_route'], '?')) {
            $params['_route'] = substr($params['_route'], 0, $pos);
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        if ($fragment = strstr($name, '#')) {
            $name = strstr($name, '#', true);
        }

        if ($query = substr(strstr($name, '?'), 1)) {
            parse_str($query, $params);
            $name = strstr($name, '?', true);
            $parameters = array_replace($parameters, $params);
        }

        if ($referenceType !== self::LINK_URL) {
            if ($alias = $this->getAlias($this->getGenerator()->generate($name, $parameters, 'link')) and is_callable($alias[2])) {
                $parameters = call_user_func($alias[2], $parameters);
            }
        }

        return $this->getGenerator()->generate($name, $parameters, $referenceType) . $fragment;
    }

    /**
     * Gets cache info.
     *
     * @param  string $file
     * @return array|null
     */
    protected function getCache($file)
    {
        if (!$this->options['cache']) {
            return null;
        }

        if (!$this->cache) {

            $modified  = 0;
            $resources = $this->controllers->getResources();

            foreach ($this->aliases as $name => $alias) {
                $resources['aliases'][] = $name.$alias[0];
            }

            foreach ($resources['controllers'] as $controller) {
                if (file_exists($controller) && ($time = filemtime($controller)) > $modified) {
                    $modified = $time;
                }
            }

            $this->cache = ['key' => sha1(json_encode($resources)), 'modified' => $modified];
        }

        $file  = sprintf($file, $this->options['cache'], $this->cache['key']);
        $time  = file_exists($file) ? filemtime($file) : 0;
        $fresh = $time >= $this->cache['modified'];

        return array_merge(compact('fresh', 'file'), $this->cache);
    }

    /**
     * Writes cache file.
     *
     * @param  string $file
     * @param  string $content
     * @throws \RuntimeException
     */
    protected function writeCache($file, $content)
    {
        if (!file_put_contents($file, $content)) {
            throw new \RuntimeException("Failed to write cache file ($file).");
        }
    }
}
