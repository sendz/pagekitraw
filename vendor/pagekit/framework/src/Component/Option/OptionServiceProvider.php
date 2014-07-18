<?php

namespace Pagekit\Component\Option;

use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;

class OptionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['option'] = function ($app) {
            return new Option($app['db'], $app['cache'], $app['config']['option.table']);
        };
    }

    public function boot(Application $app)
    {
    }
}
