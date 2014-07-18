<?php

namespace Pagekit\Component\Cache;

use Doctrine\Common\Cache\Cache as BaseCache;

interface CacheInterface extends BaseCache
{
    /**
     * Retrieves the namespace that prefixes all cache ids.
     *
     * @return string
     */
    public function getNamespace();

    /**
     * Sets the namespace to prefix all cache ids with.
     *
     * @param string $namespace
     */
    public function setNamespace($namespace);

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    public function flushAll();

    /**
     * Returns list of supported caches or boolean for individual cache.
     *
     * @param  string $name
     * @return array|boolean
     */
    public static function supports($name = null);
}
