<?php

namespace Pagekit\Component\Routing;

use Pagekit\Component\Filter\FilterManager;
use Pagekit\Component\Routing\Controller\ControllerCollection;
use Pagekit\Component\Routing\Controller\ControllerReader;
use Pagekit\Component\Routing\Event\ConfigureRouteListener;
use Pagekit\Component\Routing\Event\JsonResponseListener;
use Pagekit\Component\Routing\Event\StringResponseListener;
use Pagekit\Component\Routing\Loader\RouteLoader;
use Pagekit\Component\Routing\Request\Event\ParamFetcherListener;
use Pagekit\Component\Routing\Request\ParamFetcher;
use Pagekit\Component\Routing\Request\ParamReader;
use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;

class RoutingServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['router'] = function($app) {
            return new Router($app['kernel'], $app['controllers'], ['cache' => $app['path.cache']]);
        };

        $app['kernel'] = function($app) {
            return new HttpKernel($app['events'], $app['controllers'], $app['request_stack']);
        };

        $app['request_stack'] = function () {
            return new RequestStack;
        };

        $app['response'] = function($app) {
            return new Response($app['url']);
        };

        $app['resolver'] = function() {
            return new ControllerResolver;
        };

        $app['controllers'] = function($app) {

            $reader = new ControllerReader($app['events']);
            $loader = new RouteLoader($reader);

            return new ControllerCollection($loader, $app['resolver']);
        };

        $app['url'] = function($app) {
            return new UrlProvider($app['router'], $app['locator']);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $app['events']->addSubscriber(new ConfigureRouteListener);
        $app['events']->addSubscriber(new ParamFetcherListener(new ParamFetcher(new FilterManager)));
        $app['events']->addSubscriber(new RouterListener($app['router'], null, null, $app['request_stack']));
        $app['events']->addSubscriber(new ResponseListener('UTF-8'));
        $app['events']->addSubscriber(new JsonResponseListener);
        $app['events']->addSubscriber(new StringResponseListener);
    }
}
