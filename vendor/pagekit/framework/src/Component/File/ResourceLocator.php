<?php

namespace Pagekit\Component\File;

use Pagekit\Component\File\Exception\InvalidArgumentException;

/**
 * Uniform resource locator class.
 *
 * @link http://webmozarts.com/2013/06/19/the-power-of-uniform-resource-location-in-php/
 */
class ResourceLocator
{
    /**
     * The schemes.
     *
     * @var array
     */
    protected $schemes = [];

    /**
     * Add path(s) to locator.
     *
     * @param string       $scheme
     * @param string       $prefix
     * @param string|array $paths
     */
    public function addPath($scheme, $prefix, $paths)
    {
        $paths = array_map(function($path) use ($prefix) {
            return [$prefix, rtrim($path, '\/')];
        }, (array) $paths);

        if (isset($this->schemes[$scheme])) {
            $paths = array_merge($paths, $this->schemes[$scheme]);
        }

        $this->schemes[$scheme] = $paths;
    }

    /**
     * Find a resource.
     *
     * @param  string $uri
     * @return string|false
     */
    public function findResource($uri)
    {
        return $this->find($uri, true);
    }

    /**
     * Find the resource variants.
     *
     * @param  string $uri
     * @return array
     */
    public function findResourceVariants($uri)
    {
        return $this->find($uri);
    }

    /**
     * Find the first resource or all resource variants.
     *
     * @param  string $uri
     * @param  bool $first
     * @throws InvalidArgumentException
     * @return array|string|false
     */
    protected function find($uri, $first = false)
    {
        if (strpos($uri, '://') > 0 && $segments = explode('://', $uri, 2)) {
            list($scheme, $file) = $segments;
        } else {
            throw new InvalidArgumentException('Invalid resource uri format');
        }

        if (!isset($this->schemes[$scheme])) {
            throw new InvalidArgumentException("Invalid resource scheme {$scheme}://");
        }

        $paths = $first ? false : [];

        foreach ($this->schemes[$scheme] as $parts) {

            list($prefix, $path) = $parts;

            if ($length = strlen($prefix) and 0 !== strpos($file, $prefix)) {
                continue;
            }

            if (file_exists($p = $path.'/'.ltrim(substr($file, $length), '\/'))) {

                if ($first) {
                    return $p;
                }

                $paths[] = $p;
            }
        }

        return $paths;
    }
}
