<?php

namespace Pagekit\Component\File\StreamWrapper;

use Pagekit\Component\File\ResourceLocator;

class ResourceLocatorBasedStreamWrapper extends StreamWrapper
{
    /**
     * @var ResourceLocator
     */
    protected static $locator;

    /**
     * @param ResourceLocator $locator
     */
    public static function setLocator(ResourceLocator $locator)
    {
        self::$locator = $locator;
    }

    /**
     * {@inheritdoc}
     */
    public function stream_open($uri, $mode, $options, &$opened_url)
    {
        if (!$path = $this->getLocalPath($uri, $mode)) {
            return false;
        }

        $this->handle = fopen($path, $mode);

        return (bool) $this->handle;
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir($uri, $mode, $options)
    {
        if (!$path = $this->getLocalPath($uri, $mode)) {
            return false;
        }

        return mkdir($path, $mode, $options & STREAM_MKDIR_RECURSIVE);
    }

    protected function getLocalPath($uri, $mode)
    {
        if ($path = $this->getTarget($uri)) {
            return $path;
        }

        if (in_array($mode, ['r', 'r+'])) {
            return false;
        }

        list($scheme, $target) = explode('://', $uri, 2);

        if (!$path = $this->getTarget($scheme.'://'.dirname($target))) {
            return false;
        }

        return $path.'/'.basename($uri);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTarget($uri)
    {
        return self::$locator->findResource($uri);
    }
}
