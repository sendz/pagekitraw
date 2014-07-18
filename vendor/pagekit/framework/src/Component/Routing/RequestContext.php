<?php

namespace Pagekit\Component\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext as BaseRequestContext;

class RequestContext extends BaseRequestContext
{
    protected $basePath;
    protected $scriptPath;
    protected $referer;
    protected $schemeAndHttpHost;

    /**
     * {@inheritdoc}
     */
    public function fromRequest(Request $request)
    {
        parent::fromRequest($request);

        $this->setBasePath($path = $request->getBasePath());
        $this->setBaseUrl($request->server->get('HTTP_MOD_REWRITE') == 'On' ? $path : "$path/index.php");
        $this->setScriptPath($request->server->get('SCRIPT_FILENAME'));
        $this->setReferer($request->headers->get('referer'));
        $this->setSchemeAndHttpHost($request->getSchemeAndHttpHost());
    }

    /**
     * Gets the base path.
     *
     * @return string The base path
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Sets the base Path.
     *
     * @param string $basePath The base path
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Gets the referer
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Sets the referer
     *
     * @param string $referer
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;
    }

    /**
     * Gets the script's base path.
     *
     * @return string
     */
    public function getScriptPath()
    {
        return $this->scriptPath;
    }

    /**
     * Sets the script's base path.
     *
     * @param string $scriptPath
     */
    public function setScriptPath($scriptPath)
    {
        $this->scriptPath = str_replace('\\', '/', dirname(realpath($scriptPath)));
    }

    /**
     * Gets the Scheme and Http Host
     *
     * @return string
     */
    public function getSchemeAndHttpHost()
    {
        return $this->schemeAndHttpHost;
    }

    /**
     * Sets the Scheme and Http Host
     *
     * @param string $schemeAndHttpHost
     */
    public function setSchemeAndHttpHost($schemeAndHttpHost)
    {
        $this->schemeAndHttpHost = $schemeAndHttpHost;
    }
}
