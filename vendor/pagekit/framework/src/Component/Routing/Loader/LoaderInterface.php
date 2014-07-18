<?php

namespace Pagekit\Component\Routing\Loader;

use Symfony\Component\Routing\RouteCollection;

interface LoaderInterface
{
    /**
     * Loads a routes collection by parsing controller method names.
     *
     * @param  string $controller
     * @param  array  $options
     * @return RouteCollection
     */
    public function load($controller, array $options = []);
}
