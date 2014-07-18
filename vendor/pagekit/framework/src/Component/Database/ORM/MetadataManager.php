<?php

namespace Pagekit\Component\Database\ORM;

use Pagekit\Component\Cache\CacheInterface;
use Pagekit\Component\Database\Connection;
use Pagekit\Component\Database\ORM\Loader\LoaderInterface;

class MetadataManager
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var Metadata[]
     */
    protected $metadata = [];

    /**
     * The cache prefix
     *
     * @var string $prefix
     */
    protected $prefix = 'Metadata.';

    /**
     * Constuctor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Gets the database connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Sets the loader object used by the factory to create Metadata objects.
     *
     * @param LoaderInterface $loader
     */
    public function setLoader(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Gets the cache used for caching Metadata objects.
     *
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Sets the cache used for caching Metadata objects.
     *
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Checks if the metadata for a class is already loaded.
     *
     * @param  string  $class
     * @return bool
     */
    public function has($class)
    {
        return isset($this->metadata[$class]);
    }

    /**
     * Gets the metadata for the given class.
     *
     * @param  object|string $class
     * @return Metadata
     */
    public function get($class)
    {
        $class = new \ReflectionClass($class);
        $name  = $class->getName();

        if (!isset($this->metadata[$name])) {

            if ($this->cache) {

                $id = sprintf('%s%s.%s', $this->prefix, filemtime($class->getFileName()), $name);

                if ($config = $this->cache->fetch($id)) {
                    $this->metadata[$name] = new Metadata($this, $name, $config);
                } else {
                    $this->cache->save($id, $this->load($class)->getConfig());
                }

            } else {
                $this->load($class);
            }
        }

        return $this->metadata[$name];
    }

    /**
     * Loads the metadata of the given class.
     *
     * @param \ReflectionClass $class
     * @return Metadata
     */
    protected function load(\ReflectionClass $class)
    {
        $parent = null;

        foreach ($this->getParentClasses($class) as $class) {

            $name = $class->getName();

            if (isset($this->metadata[$name])) {
                $parent = $this->metadata[$name];
                continue;
            }

            $config = [];

            if ($parent) {

                foreach ($parent->getFields() as $field) {

                    if (!isset($field['inherited']) && !$parent->isMappedSuperclass()) {
                        $field['inherited'] = $parent->getClass();
                    }

                    $config['fields'][$field['name']] = $field;
                }

                foreach ($parent->getRelationMappings() as $relation) {

                    if (!isset($relation['inherited']) && !$parent->isMappedSuperclass()) {
                        $relation['inherited'] = $parent->getClass();
                    }

                    $config['relations'][$relation['name']] = $relation;
                }

                if ($identifer = $parent->getIdentifier()) {
                    $config['identifier'] = $identifer;
                }

                $config['events']     = $parent->getEvents();

                if ($parent->isMappedSuperclass()) {
                    $config['repositoryClass'] = $parent->getRepositoryClass();
                }
            }

            $this->metadata[$name] = $parent = new Metadata($this, $name, $this->loader->load($class, $config));
        }

        return $parent;
    }

    /**
     * Get array of parent classes for the given class.
     *
     * @param  \ReflectionClass $class
     * @return array
     */
    protected function getParentClasses(\ReflectionClass $class)
    {
        $parents = [$class];

        while ($parent = $class->getParentClass()) {

            if (!$this->loader->isTransient($parent)) {
                array_unshift($parents, $parent);
            }

            $class = $parent;
        }

        return $parents;
    }
}
