<?php

namespace Pagekit\Framework\Provider;

use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

class ExceptionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $debug   = isset($app['config']) ? $app['config']['app.debug'] : true;
        $handler = ExceptionHandler::register($debug);

        ErrorHandler::register(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR);

        if ($cli = $app->runningInConsole() or $debug) {
            ini_set('display_errors', 1);
        }

        $app['exception'] = $handler;
    }

    public function boot(Application $app)
    {
    }
}
