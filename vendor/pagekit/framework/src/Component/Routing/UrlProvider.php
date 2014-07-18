<?php

namespace Pagekit\Component\Routing;

use Pagekit\Component\File\Exception\InvalidArgumentException;
use Pagekit\Component\File\ResourceLocator;
use Pagekit\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class UrlProvider
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var ResourceLocator
     */
    protected $locator;

    /**
     * Constructor.
     *
     * @param Router          $router
     * @param ResourceLocator $locator
     */
    public function __construct(Router $router, ResourceLocator $locator)
    {
        $this->router  = $router;
        $this->context = $router->getContext();
        $this->locator = $locator;
    }

    /**
     * Get the base path for the current request.
     *
     * @param  mixed $referenceType
     * @return string
     */
    public function base($referenceType = UrlGenerator::ABSOLUTE_PATH)
    {
        $url = $this->context->getBasePath();

        if ($referenceType === UrlGenerator::ABSOLUTE_URL) {
            $url = $this->context->getSchemeAndHttpHost().$url;
        }

        return $url;
    }

    /**
     * Get the URL for the current request.
     *
     * @param  mixed $referenceType
     * @return string
     */
    public function current($referenceType = UrlGenerator::ABSOLUTE_PATH)
    {
        $url = $this->context->getBaseUrl();

        if ($referenceType === UrlGenerator::ABSOLUTE_URL) {
            $url = $this->context->getSchemeAndHttpHost().$url;
        }

        if ($qs = $this->context->getQueryString()) {
            $qs = '?'.$qs;
        }

        return $url.$this->context->getPathInfo().$qs;
    }

    /**
     * Get the URL for the previous request.
     *
     * @return string
     */
    public function previous()
    {
        if ($referer = $this->context->getReferer()) {
            return $this->to($referer);
        }

        return '';
    }

    /**
     * Get the URL to a path or locator resource.
     *
     * @param  string $path
     * @param  mixed  $parameters
     * @param  mixed  $referenceType
     * @return string
     */
    public function to($path = '', $parameters = [], $referenceType = UrlGenerator::ABSOLUTE_PATH)
    {
        if (0 === strpos($path, '@')) {
            return $this->route($path, $parameters, $referenceType);
        }

        try {

            if (filter_var($path, FILTER_VALIDATE_URL) !== false) {
                $path = $this->locator->findResource($path);
            }

        } catch (InvalidArgumentException $e) {
            return $path;
        }

        if ($this->isAbsolutePath($path)) {
            $path = str_replace('\\', '/', $path);
            $path = strpos($path, $base = $this->context->getScriptPath()) === 0 ? substr($path, strlen($base)) : $path;
        }

        if ($query = http_build_query($parameters, '', '&')) {
            $query = '?'.$query;
        }

        if ($referenceType !== UrlGenerator::BASE_PATH) {
            $path = $this->base($referenceType).'/'.trim($path, '/');
        }

        return $path.$query;
    }

    /**
     * Get the URL to a named route.
     *
     * @param  string $name
     * @param  mixed  $parameters
     * @param  mixed  $referenceType
     * @return string|false
     */
    public function route($name = '', $parameters = [], $referenceType = UrlGenerator::ABSOLUTE_PATH)
    {
        try {

            return $this->router->generate($name, $parameters, $referenceType);

        } catch (RouteNotFoundException $e) {}

        return false;
    }

    /**
     * Returns whether the file path is an absolute path.
     *
     * @param  string $file
     * @return bool
     */
    protected function isAbsolutePath($file)
    {
        return $file && ($file[0] == '/' || $file[0] == '\\' || (strlen($file) > 3 && ctype_alpha($file[0]) && $file[1] == ':' && ($file[2] == '\\' || $file[2] == '/')));
    }
}
