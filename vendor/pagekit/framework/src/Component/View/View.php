<?php

namespace Pagekit\Component\View;

use Pagekit\Component\View\Section\SectionManager;
use Symfony\Component\Templating\DelegatingEngine;
use Symfony\Component\Templating\EngineInterface;

class View implements ViewInterface
{
    /**
     * @var SectionManager
     */
    protected $sections;

    /**
     * @var EngineInterface
     */
    protected $engine;

    /**
     * @var string|false
     */
    protected $layout = false;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * Constructor.
     *
     * @param EngineInterface $engine
     * @param SectionManager  $sections
     */
    public function __construct(SectionManager $sections = null, EngineInterface $engine = null)
    {
        $this->sections = $sections ?: new SectionManager;
        $this->engine   = $engine ?: new DelegatingEngine;
    }

    /**
     * Gets the template engine.
     *
     * @return EngineInterface
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * Sets the template engine.
     *
     * @param EngineInterface $engine
     */
    public function setEngine(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * @return SectionManager
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * {@inheritdoc}
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * {@inheritdoc}
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * Gets a parameter.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $array = $this->parameters;

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {

            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Sets a parameter.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $array =& $this->parameters;

        while (count($keys) > 1) {

            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array =& $array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $parameters = [])
    {
        foreach ($parameters as $key => $value) {
            if (strpos($key, '.') !== false) {
                $this->set($key, $value);
            }
        }

        return $this->engine->render($name, array_replace($this->parameters, $parameters));
    }
}
