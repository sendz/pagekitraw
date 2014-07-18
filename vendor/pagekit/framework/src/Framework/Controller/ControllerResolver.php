<?php

namespace Pagekit\Framework\Controller;

use Pagekit\Framework\Application;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;

class ControllerResolver extends BaseControllerResolver
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * Constructor.
     *
     * @param Application     $app
     * @param LoggerInterface $logger
     */
    public function __construct(Application $app, LoggerInterface $logger = null)
    {
        parent::__construct($logger);

        $this->app = $app;
    }

    /**
     * @{inheritdoc}
     */
    protected function createController($controller)
    {
        if (strpos($controller, '::') === false) {
            throw new \InvalidArgumentException(sprintf('Unable to find controller "%s".', $controller));
        }

        list($class, $method) = explode('::', $controller, 2);

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        $reflection = new \ReflectionClass($class);

        if ($constructor = $reflection->getConstructor()) {

            $args = [];

            foreach ($constructor->getParameters() as $param) {
                if ($class = $param->getClass()) {

                    if ($class->isInstance($this->app)) {
                        $args[] = $this->app;
                    } elseif ($extension = $this->app['extensions']->get($class->getName())) {
                        $args[] = $extension;
                    }

                } else {
                    throw new \InvalidArgumentException(sprintf('Unknown constructor argument "$%s".', $param->getName()));
                }
            }

            $instance = $reflection->newInstanceArgs($args);
        }

        return [isset($instance) ? $instance : new $class, $method];
    }
}
