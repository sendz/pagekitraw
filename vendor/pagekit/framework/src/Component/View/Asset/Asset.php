<?php

namespace Pagekit\Component\View\Asset;

abstract class Asset implements AssetInterface
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $asset;

	/**
	 * @var array
	 */
    protected $options;

	/**
	 * Constructor.
     *
     * @param string $name
     * @param mixed  $asset
	 * @param array  $options
	 */
    public function __construct($name, $asset, array $options = [])
    {
        $this->name = $name;
        $this->asset = $asset;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
		return $this->name;
	}

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) $this->asset;
    }

    /**
     * Sets an option.
     *
     * @param string $name  The option name
     * @param mixed  $value The option value
     */
    public function offsetSet($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * Gets a option value.
     *
     * @param string $name The option name
     *
     * @return mixed The option value
     */
    public function offsetGet($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * Returns true if the option exists.
     *
     * @param string $name The option name
     *
     * @return bool true if the option exists, false otherwise
     */
    public function offsetExists($name)
    {
        return isset($this->options[$name]);
    }

    /**
     * Removes an option.
     *
     * @param string $name The option name
     */
    public function offsetUnset($name)
    {
        unset($this->options[$name]);
    }
}
