<?php

namespace Pagekit\Component\File;

use Pagekit\Component\File\StreamWrapper\ResourceLocatorBasedReadOnlyStreamWrapper;
use Pagekit\Component\File\StreamWrapper\ResourceLocatorBasedStreamWrapper;
use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;

class ResourceLocatorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['locator'] = function() {
            return new ResourceLocator;
        };
    }

    public function boot(Application $app)
    {
        if (isset($app['config']['locator.paths'])) {
            foreach($app['config']['locator.paths'] as $scheme => $paths) {
                $app['locator']->addPath($scheme, '', $paths);
            }
        }

        if (isset($app['config']['locator.wrappers'])) {

            foreach($app['config']['locator.wrappers'] as $scheme => $readonly) {
                stream_wrapper_register($scheme, $readonly ? 'Pagekit\Component\File\StreamWrapper\ResourceLocatorBasedReadOnlyStreamWrapper' : 'Pagekit\Component\File\StreamWrapper\ResourceLocatorBasedStreamWrapper');
            }

            ResourceLocatorBasedStreamWrapper::setLocator($app['locator']);
            ResourceLocatorBasedReadOnlyStreamWrapper::setLocator($app['locator']);
        }
    }
}
