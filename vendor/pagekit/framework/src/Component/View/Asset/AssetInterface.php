<?php

namespace Pagekit\Component\View\Asset;

interface AssetInterface extends \ArrayAccess
{
    /**
     * Returns the asset name.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the asset options.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Returns the asset content.
     *
     * @return string
     */
    public function getContent();

    /**
     * Sets the asset content.
     *
     * @param string $content
     */
    public function setContent($content);

    /**
     * Returns the object string representation.
     *
     * @return string
     */
    public function __toString();
}
