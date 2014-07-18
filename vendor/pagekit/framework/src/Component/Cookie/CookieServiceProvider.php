<?php

namespace Pagekit\Component\Cookie;

use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class CookieServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
		$app['cookie'] = function($app) {

            $app['cookie.init'] = true;

            $request = $app['request'];
            $path    = $app['config']->get('cookie.path', $request->getBasePath() ?: '/');
            $domain  = $app['config']->get('cookie.domain');

			return new CookieJar($request, $path, $domain);
		};
	}

    public function boot(Application $app)
    {
        $app->on('kernel.response', function(FilterResponseEvent $event) use ($app) {
			if (isset($app['cookie.init'])) {
                foreach ($app['cookie']->getQueuedCookies() as $cookie) {
                    $event->getResponse()->headers->setCookie($cookie);
                }
			}
		});
    }
}
