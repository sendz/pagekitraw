<?php

namespace Pagekit\Component\Package\Loader;

interface LoaderInterface
{
    /**
     * Creates a package instance based on a given package config.
     *
     * @param  mixed   $config
     * @param  string  $class
     * @return \Pagekit\Component\Package\PackageInterface
     */
    public function load($config, $class = 'Pagekit\Component\Package\Package');
}
