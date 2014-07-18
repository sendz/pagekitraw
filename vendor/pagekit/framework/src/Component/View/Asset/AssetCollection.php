<?php

namespace Pagekit\Component\View\Asset;

class AssetCollection implements \IteratorAggregate
{
    /**
     * @var AssetInterface[]
     */
	protected $assets = [];

   /**
     * Add asset to collection.
     *
     * @param AssetInterface $asset
     */
	public function add(AssetInterface $asset) {
		$this->assets[$asset->getName()] = $asset;
	}

    /**
     * Get asset from collection.
     *
     * @param  string $name
     * @return AssetInterface
     */
	public function get($name) {
		return isset($this->assets[$name]) ? $this->assets[$name] : null;
	}

    /**
     * Remove asset from collection.
     *
     * @param string $name
     */
	public function remove($name) {
		unset($this->assets[$name]);
	}

    /**
     * IteratorAggregate interface implementation.
     */
    public function getIterator() {
        return new \ArrayIterator($this->assets);
    }
}
