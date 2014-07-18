<?php

namespace Pagekit\Component\Database\ORM\Loader;

use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Pagekit\Component\Database\ORM\Annotation\Annotation;

class AnnotationLoader implements LoaderInterface
{
    /**
     * @var SimpleAnnotationReader
     */
    protected $reader;

    /**
     * @var string
     */
    protected $namespace = 'Pagekit\Component\Database\ORM\Annotation';

    /**
     * {@inheritdoc}
     */
    public function load(\ReflectionClass $class, array $config = [])
    {
        // @Entity
        if ($annotation = $this->getAnnotation($class, 'Entity')) {

            $config['table']           = $annotation->tableClass ?: strtolower($class->getShortName());
            $config['eventPrefix']     = $annotation->eventPrefix;
            $config['repositoryClass'] = $annotation->repositoryClass;

        // @MappedSuperclass
        } elseif ($annotation = $this->getAnnotation($class, 'MappedSuperclass')) {

            $config['isMappedSuperclass'] = true;
            $config['repositoryClass']    = $annotation->repositoryClass;

        } else {
            throw new \Exception(sprintf('No @Entity annotation found for class %s', $class->getName()));
        }

        foreach ($class->getProperties() as $property) {

            $name = $property->getName();

            if (!$property->isPrivate() && isset($config['isMappedSuperclass']) || isset($config['fields'][$name]['inherited']) || isset($config['relations'][$name]['inherited'])) {
                continue;
            }

            // @Column
            if ($annotation = $this->getAnnotation($property, 'Column')) {

                $field = compact('name');

                if (isset($config['fields'][$name])) {
                    throw new \Exception(sprintf('Duplicate field mapping detected, "%s" already exists.', $name));
                }

                if ($annotation->type) {
                    $field['type'] = $annotation->type;
                }

                if ($annotation->name) {
                    $field['column'] = $annotation->name;
                }

                if ($this->getAnnotation($property, 'Id')) {
                    $field['id'] = true;
                }

                $config['fields'][$name] = $field;

            } else {

                // @BelongsTo, @HasOne, @HasMany, @ManyToMany
                foreach (['BelongsTo', 'HasOne', 'HasMany', 'ManyToMany'] as $type) {
                    if ($annotation = $this->getAnnotation($property, $type)) {

                        if (isset($config['fields'][$name]) || isset($config['relations'][$name])) {
                            throw new \Exception(sprintf('Duplicate relation mapping detected, "%s" already exists.', $name));
                        }

                        if ($annot = $this->getAnnotation($property, 'OrderBy') and strpos($type, 'Many') !== false) {
                            $annotation->orderBy = $annot->value;
                        } else {
                            $annotation->orderBy = [];
                        }

                        $config['relations'][$name] = array_merge(compact('name', 'type'), (array) $annotation);

                        break;
                    }
                }
            }
        }

        foreach ($class->getMethods() as $method) {

            $name = $method->getName();

            if (!$method->isPublic() || $method->getDeclaringClass()->getName() != $class->getName()) {
                continue;
            }

            // @PreSave, @PostSave, @PreUpdate, @PostUpdate, @PreDelete, @PostDelete, @PostLoad
            foreach (['PreSave', 'PostSave', 'PreUpdate', 'PostUpdate', 'PreDelete', 'PostDelete', 'PostLoad'] as $event) {
                if ($annotation = $this->getAnnotation($method, $event)) {
                    $config['events'][lcfirst($event)][] = $name;
                }
            }
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient(\ReflectionClass $class)
    {
        return !$this->getAnnotation($class, 'Entity') && !$this->getAnnotation($class, 'MappedSuperclass');
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

        $name = "{$this->namespace}\\$name";

        if ($from instanceof \ReflectionClass) {
            $annotation = $this->reader->getClassAnnotation($from, $name);
        } elseif ($from instanceof \ReflectionMethod) {
            $annotation = $this->reader->getMethodAnnotation($from, $name);
        } elseif ($from instanceof \ReflectionProperty) {
            $annotation = $this->reader->getPropertyAnnotation($from, $name);
        }

        return $annotation;
    }
}
