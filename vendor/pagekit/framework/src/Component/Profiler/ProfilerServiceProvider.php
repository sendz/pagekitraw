<?php

namespace Pagekit\Component\Profiler;

use Pagekit\Component\Database\DataCollector\DatabaseDataCollector;
use Pagekit\Component\Profiler\Event\ToolbarListener;
use Pagekit\Component\Routing\DataCollector\RoutesDataCollector;
use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcherInterface;
use Symfony\Component\HttpKernel\DataCollector\EventDataCollector;
use Symfony\Component\HttpKernel\DataCollector\MemoryDataCollector;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector;
use Symfony\Component\HttpKernel\EventListener\ProfilerListener;
use Symfony\Component\HttpKernel\Profiler\SqliteProfilerStorage;
use Symfony\Component\Stopwatch\Stopwatch;

class ProfilerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!$app['config']['profiler.enabled']) {
            return;
        }

        if (!(class_exists('SQLite3') || (class_exists('PDO') && in_array('sqlite', \PDO::getAvailableDrivers(), true)))) {
            return;
        }

        $app['profiler'] = function($app) {

            $profiler = new Profiler($app['profiler.storage']);

            if ($app['events'] instanceof TraceableEventDispatcherInterface) {
                $app['events']->setProfiler($profiler);
            }

            return $profiler;
        };

        $app['profiler.storage'] = function($app) {
            return new SqliteProfilerStorage('sqlite:'.$app['config']['profiler.file'], '', '', 86400);
        };

        $app['profiler.stopwatch'] = function() {
            return new Stopwatch;
        };

        $app->extend('events', function($dispatcher, $app) {
            return new TraceableEventDispatcher($dispatcher, $app['profiler.stopwatch']);
        });
    }

    public function boot(Application $app)
    {
        if (!isset($app['profiler'])) {
            return;
        }

        if (isset($app['view'])) {
            $view = $app['view'];
            unset($app['view']);
            $app['view'] = new TraceableView($view, $app['profiler.stopwatch']);
        }

        $toolbar = __DIR__ . '/views/toolbar/';
        $panel   = __DIR__ . '/views/panel/';

        $app['profiler']->add($request = new RequestDataCollector, "$toolbar/request.php", "$panel/request.php", 40);
        $app['profiler']->add(new RoutesDataCollector($app['router'], $app['path.cache']), "$toolbar/routes.php", "$panel/routes.php", 35);
        $app['profiler']->add(new TimeDataCollector, "$toolbar/time.php", "$panel/time.php", 20);
        $app['profiler']->add(new MemoryDataCollector, "$toolbar/memory.php");
        $app['profiler']->add(new EventDataCollector, "$toolbar/events.php", "$panel/events.php", 30);

        if (isset($app['db']) && isset($app['db.debug_stack'])) {
            $app['profiler']->add(new DatabaseDataCollector($app['db'], $app['db.debug_stack']), "$toolbar/db.php", "$panel/db.php", -10);
            $app['db']->getConfiguration()->setSQLLogger($app['db.debug_stack']);
        }

        $app['events']->addSubscriber($request);
        $app['events']->addSubscriber(new ProfilerListener($app['profiler']));
        $app['events']->addSubscriber(new ToolbarListener($app['profiler'], $app['view'], $app['url'], $app['controllers']));
    }
}
