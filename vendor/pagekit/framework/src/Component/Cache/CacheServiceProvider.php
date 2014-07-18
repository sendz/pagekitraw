<?php

namespace Pagekit\Component\Cache;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $supports = Cache::supports();

        foreach ($app['config']['cache'] as $name => $config) {
            $app[$name] = function() use ($config, $supports) {

                if (!isset($config['storage'])) {
                    throw new \RuntimeException('Cache storage missing.');
                }

                if ($config['storage'] == 'auto' || !in_array($config['storage'], $supports)) {
                    $config['storage'] = end($supports);
                }

                switch ($config['storage']) {

                    case 'array':
                        $storage = new ArrayCache;
                        break;

                    case 'apc':
                        $storage = new ApcCache;
                        break;

                    case 'file':
                        $storage = new FilesystemCache($config['path']);
                        break;

                    case 'phpfile':
                        $storage = new PhpFileCache($config['path']);
                        break;

                    default:
                        throw new \RuntimeException('Unknown cache storage.');
                        break;
                }

                $cache = new Cache($storage);

                if ($prefix = isset($config['prefix']) ? $config['prefix'] : false) {
                    $cache->setNamespace($prefix);
                }

                return $cache;
            };
        }
    }

    public function boot(Application $app)
    {
    }
}
