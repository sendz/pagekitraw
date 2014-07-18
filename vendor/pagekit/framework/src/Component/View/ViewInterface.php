<?php

namespace Pagekit\Component\View;

interface ViewInterface
{
    /**
     * Gets the layout template.
     *
     * @return string
     */
    public function getLayout();

    /**
     * Sets the layout template.
     *
     * @param string $layout
     */
    public function setLayout($layout);

    /**
     * Renders a template.
     *
     * @param  string $name
     * @param  array  $parameters
     *
     * @return string
     */
    public function render($name, array $parameters = []);
}
