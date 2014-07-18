<?php

namespace Pagekit\Framework\Provider;

use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['events'] = function() {
            return new EventDispatcher;
        };
    }

    public function boot(Application $app)
    {
    }
}
