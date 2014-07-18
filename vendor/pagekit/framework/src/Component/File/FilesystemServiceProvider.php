<?php

namespace Pagekit\Component\File;

use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;

class FilesystemServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['files'] = function() {
            return new Filesystem;
        };
    }

    public function boot(Application $app)
    {
    }
}
