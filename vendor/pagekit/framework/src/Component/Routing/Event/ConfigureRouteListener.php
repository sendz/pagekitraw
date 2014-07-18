<?php

namespace Pagekit\Component\Routing\Event;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigureRouteListener implements EventSubscriberInterface
{
    protected $reader;
    protected $namespace;

    /**
     * Constructor.
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader = null)
    {
        $this->reader = $reader;
        $this->namespace = 'Pagekit\Component\Routing\Annotation';
    }

    /**
     * Reads the @Request and @Response annotations.
     *
     * @param ConfigureRouteEvent $event
     */
    public function onConfigureRoute(ConfigureRouteEvent $event)
    {
        foreach (['_request' => 'Request', '_response' => 'Response'] as $name => $class) {
            if ($annotation = $this->getAnnotation($event->getMethod(), $class)) {
                if ($data = $annotation->getData()) {
                    $event->getRoute()->setDefault($name, $data);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'route.configure' => 'onConfigureRoute'
        ];
    }

    /**
     * Gets an annotation.
     *
     * @param  mixed  $from
     * @param  string $name
     * @return Annotation
     */
    protected function getAnnotation($from, $name)
    {
        if (!$this->reader) {
            $this->reader = new SimpleAnnotationReader;
            $this->reader->addNamespace($this->namespace);
        }

        return $this->reader->getMethodAnnotation($from, "{$this->namespace}\\$name");
    }
}
