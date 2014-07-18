<?php

namespace Pagekit\Component\Routing\Controller;

use ReflectionClass;
use Symfony\Component\Routing\RouteCollection;

interface ControllerReaderInterface
{
    /**
     * Reads controller routes.
     *
     * @param  ReflectionClass $class
     * @param  array           $options
     * @return RouteCollection
     */
    public function read(ReflectionClass $class, array $options = []);

    /**
     * Returns true if this reader supports the given controller.
     *
     * @param  ReflectionClass $class
     * @return bool
     */
    public function supports(ReflectionClass $class);
}
