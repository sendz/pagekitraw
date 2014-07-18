<?php

namespace Pagekit\Component\Cache;

use Doctrine\Common\Cache\CacheProvider;

class Cache implements CacheInterface
{
    /**
     * @var CacheProvider
     */
    protected $provider;

    /**
     * The cache storage to delegate to.
     *
     * @param CacheProvider $provider
     */
    public function __construct(CacheProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->provider->contains($id);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->provider->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        return $this->provider->fetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return $this->provider->getStats();
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return $this->provider->save($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return $this->provider->getNamespace();
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace($namespace)
    {
        $this->provider->setNamespace($namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return $this->provider->flushAll();
    }

    /**
     * {@inheritdoc}
     */
    public static function supports($name = null)
    {
        $supports = ['phpfile', 'array', 'file'];

        if (extension_loaded('apc') && class_exists('\APCIterator')) {
            $supports[] = 'apc';
        }

        return $name? in_array($name, $supports) : $supports;
    }
}
