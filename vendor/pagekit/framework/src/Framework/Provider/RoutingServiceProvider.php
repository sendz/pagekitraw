<?php

namespace Pagekit\Framework\Provider;

use Pagekit\Component\Routing\RoutingServiceProvider as BaseRoutingServiceProvider;
use Pagekit\Framework\Application;
use Pagekit\Framework\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RoutingServiceProvider extends BaseRoutingServiceProvider
{
    public function register(Application $app)
    {
        parent::register($app);

        $app['resolver'] = function($app) {
            return new ControllerResolver($app);
        };
    }

    public function boot(Application $app)
    {
        parent::boot($app);

        // redirect the request if it has a trailing slash
		if (!$app->runningInConsole()) {

	        $app->on('kernel.request', function(GetResponseEvent $event) {

				$path = $event->getRequest()->getPathInfo();

				if ('/' != $path && '/' == substr($path, -1) && '//' != substr($path, -2)) {
                    $event->setResponse(new RedirectResponse(rtrim($event->getRequest()->getUriForPath($path), '/'), 301));
				}

	        }, 200);

		}
    }
}
