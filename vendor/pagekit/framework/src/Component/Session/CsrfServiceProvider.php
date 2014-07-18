<?php

namespace Pagekit\Component\Session;

use Pagekit\Component\Session\Csrf\Event\CsrfListener;
use Pagekit\Component\Session\Csrf\Provider\SessionCsrfProvider;
use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;

class CsrfServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['csrf'] = function($app) {
            return new SessionCsrfProvider($app['session']);
        };
    }

    public function boot(Application $app)
    {
        $app['events']->addSubscriber(new CsrfListener($app['csrf']));
    }
}
