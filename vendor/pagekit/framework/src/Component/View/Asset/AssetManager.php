<?php

namespace Pagekit\Component\View\Asset;

use Pagekit\Component\Routing\UrlProvider;

class AssetManager implements \IteratorAggregate
{
    /**
     * @var UrlProvider
     */
    protected $url;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var AssetCollection
     */
    protected $registered;

    /**
     * @var array
     */
    protected $queued = [];

    /**
     * Constructor.
     *
     * @param UrlProvider $url
     * @param string       $version
     */
    public function __construct(UrlProvider $url, $version = null)
    {
        $this->url = $url;
        $this->version = $version;
        $this->registered = new AssetCollection;
    }

    /**
     * Create a asset instance.
     *
     * @param  string $name
     * @param  mixed $asset
     * @param  array $dependencies
     * @param  array $options
     * @throws \InvalidArgumentException
     * @return AssetInterface
     */
    protected function create($name, $asset, $dependencies = [], $options = [])
    {
        if (is_string($options)) {
            $options = ['type' => $options];
        }

        if (!isset($options['type'])) {
            $options['type'] = 'file';
        }

        if ($dependencies) {
            $options = array_merge($options, ['dependencies' => (array) $dependencies]);
        }

        if ('string' == $options['type']) {
            return new StringAsset($name, $asset, $options);
        }

        if ('file' == $options['type']) {

            if (isset($options['version'])) {
                $ver = $options['version'];
            } elseif ($this->version) {
                $ver = $options['version'] = $this->version;
            } else {
                $ver = null;
            }

            $options['path'] = $asset;

            return new FileAsset($name, $this->url->to($options['path'], $ver ? compact('ver') : []), $options);
        }

        throw new \InvalidArgumentException('Unable to determine asset type.');
    }

    /**
     * Registers an asset.
     *
     * @param  string $name
     * @param  mixed  $asset
     * @param  array  $dependencies
     * @param  array  $options
     * @return self
     */
    public function register($name, $asset, $dependencies = [], $options = [])
    {
        $this->registered->add($this->create($name, $asset, $dependencies, $options));

        return $this;
    }

    /**
     * Unregisters an asset.
     *
     * @param  string $name
     * @return self
     */
    public function unregister($name)
    {
        $this->registered->remove($name);
        $this->dequeue($name);

        return $this;
    }

    /**
     * Queues a previously registered asset or a new asset.
     *
     * @param  string $name
     * @param  mixed  $asset
     * @param  array  $dependencies
     * @param  array  $options
     * @return self
     */
    public function queue($name, $asset = null, $dependencies = [], $options = [])
    {
        if (!$instance = $this->registered->get($name)) {
            $this->registered->add($instance = $this->create($name, $asset, $dependencies, $options));
        }

        $this->queued[$instance->getName()] = true;

        return $this;
    }

    /**
     * Dequeues an asset.
     *
     * @param  string $name
     * @return self
     */
    public function dequeue($name)
    {
        unset($this->queued[$name]);

        return $this;
    }

    /**
     * IteratorAggregate interface implementation.
     */
    public function getIterator()
    {
        $assets = [];

        foreach (array_keys($this->queued) as $name) {
            $this->resolveDependencies($this->registered->get($name), $assets);
        }

        return new \ArrayIterator($assets);
    }

    /**
     * Resolve asset dependencies.
     *
     * @param AssetInterface $asset
     * @param array          $resolved
     * @param array          $unresolved
     * @throws \RuntimeException
     */
    protected function resolveDependencies($asset, &$resolved, &$unresolved = [])
    {
        $unresolved[$asset->getName()] = $asset;

        if (isset($asset['dependencies'])) {
            foreach ($asset['dependencies'] as $dependency) {
                if (!isset($resolved[$dependency])) {

                    if (!$this->registered->get($dependency)) {
                        throw new \RuntimeException(sprintf('Asset dependency "%s" does not exists.', $dependency));
                    }

                    if (isset($unresolved[$dependency])) {
                        throw new \RuntimeException(sprintf('Circular asset dependency "%s > %s" detected.', $asset->getName(), $dependency));
                    }

                    $this->resolveDependencies($this->registered->get($dependency), $resolved, $unresolved);
                }
            }
        }

        $resolved[$asset->getName()] = $asset;
        unset($unresolved[$asset->getName()]);
    }
}