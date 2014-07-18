<?php

namespace Pagekit\Framework;

interface ServiceProviderInterface
{
    /**
     * Registers services on the given application.
     *
     * @param Application $app
     */
    public function register(Application $app);

    /**
     * Bootstraps the application.
     *
     * @param Application $app
     */
    public function boot(Application $app);
}
