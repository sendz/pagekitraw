<?php

namespace Pagekit\Component\Routing\Generator;

use Symfony\Component\Routing\Generator\UrlGenerator as BaseUrlGenerator;

class UrlGenerator extends BaseUrlGenerator implements UrlGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    protected function doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, array $requiredSchemes = [])
    {
        $link = $name;

        if ($params = array_intersect_key($parameters, array_flip(isset($defaults['_variables']) ? $defaults['_variables'] : $variables))) {

            $link .= '?'.http_build_query($params);

            if ($properties = $this->getRouteProperties($link)) {
                list($variables, $defaults, $requirements, $tokens, $hostTokens, $requiredSchemes) = $properties;
            }
        }

        if ($referenceType === self::LINK_URL) {
            return $link;
        }

        $url = parent::doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, $requiredSchemes);

        if ($referenceType === self::BASE_PATH) {
            $url = substr($url, strlen($this->context->getBaseUrl()));
        }

        return $url;
    }

    /**
     * Gets the properties of a route.
     *
     * @param  string $name
     * @return array
     */
    protected function getRouteProperties($name)
    {
        if ($route = $this->routes->get($name)) {

            $compiled = $route->compile();

            return [$compiled->getVariables(), $route->getDefaults(), $route->getRequirements(), $compiled->getTokens(), $compiled->getHostTokens(), $route->getSchemes()];
        }
    }
}
