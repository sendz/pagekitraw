<?php

namespace Pagekit\Component\Filter;

use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;

class FilterServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['filter'] = function($app) {
            return new FilterManager($app['filter.defaults']);
        };

        $app['filter.defaults'] = null;
    }

    public function boot(Application $app)
    {
    }
}
