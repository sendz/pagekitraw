<?php

namespace Pagekit\Component\Filter;


class FilterManager
{
    /**
     * @var array
     */
    protected $defaults = [
        'addrelnofollow' => 'Pagekit\Component\Filter\AddRelNofollow',
        'alnum'          => 'Pagekit\Component\Filter\Alnum',
        'alpha'          => 'Pagekit\Component\Filter\Alpha',
        'bool'           => 'Pagekit\Component\Filter\Boolean',
        'boolean'        => 'Pagekit\Component\Filter\Boolean',
        'digits'         => 'Pagekit\Component\Filter\Digits',
        'int'            => 'Pagekit\Component\Filter\Int',
        'integer'        => 'Pagekit\Component\Filter\Int',
        'json'           => 'Pagekit\Component\Filter\Json',
        'pregreplace'    => 'Pagekit\Component\Filter\PregReplace',
        'string'         => 'Pagekit\Component\Filter\String',
        'stripnewlines'  => 'Pagekit\Component\Filter\StripNewlines'
    ];

    /**
     * @var FilterInterface[]
     */
    protected $filters = [];

    /**
     * Constructor.
     *
     * @param array $defaults
     */
    public function __construct(array $defaults = null) {
        if (null !== $defaults) {
            $this->defaults = $defaults;
        }
    }

    /**
     * Gets a filter by name.
     *
     * @param  string $name
     * @param  array  $options
     * @return FilterInterface The filter
     * @throws \InvalidArgumentException
     */
    public function get($name, array $options = [])
    {
        if (array_key_exists($name, $this->defaults)) {
            $this->filters[$name] = $this->defaults[$name];
        }

        if (!array_key_exists($name, $this->filters)) {
            throw new \InvalidArgumentException(sprintf('Filter "%s" is not defined.', $name));
        }

        if (is_string($class = $this->filters[$name])) {
            $this->filters[$name] = new $class;
        }

        $filter = clone $this->filters[$name];
        $filter->setOptions($options);

        return $filter;
    }

    /**
     * Registers a filter.
     *
     * @param string $name
     * @param string|FilterInterface $filter
     * @throws \InvalidArgumentException
     */
    public function register($name, $filter)
    {
        if (array_key_exists($name, $this->filters)) {
            throw new \InvalidArgumentException(sprintf('Filter with the name "%s" is already defined.', $name));
        }

        if (is_string($filter) && !class_exists($filter)) {
            throw new \InvalidArgumentException(sprintf('Unknown filter with the class name "%s".', $filter));
        }

        $this->filters[$name] = $filter;
    }
}
