<?php

namespace Pagekit\Component\View;

use Pagekit\Component\View\Asset\AssetInterface;
use Pagekit\Component\View\Asset\AssetManager;
use Pagekit\Component\View\Asset\FileAsset;
use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;

class AssetServiceProvider implements ServiceProviderInterface
{
    protected $app;

    public function register(Application $app)
    {
        $this->app = $app;

        $app['view.styles'] = function($app) {
            return new AssetManager($app['url'], $app['config']['app.version']);
        };

        $app['view.scripts'] = function($app) {
            return new AssetManager($app['url'], $app['config']['app.version']);
        };
    }

    public function boot(Application $app)
    {
        $app['view.sections']->append('head', function() use ($app) {

            $result = [];

            foreach ($app['view.styles'] as $style) {

                $attributes = $this->getDataAttributes($style);

                if ($style instanceof FileAsset) {
                    $result[] = sprintf('        <link href="%s" rel="stylesheet"%s>', $style, $attributes);
                } else {
                    $result[] = sprintf('        <style%s>%s</style>', $attributes, $style);
                }
            }

            foreach ($app['view.scripts'] as $script) {

                $attributes = $this->getDataAttributes($script);

                if ($script instanceof FileAsset) {
                    $result[] = sprintf('        <script src="%s"%s></script>', $script, $attributes);
                } else {
                    $result[] = sprintf('        <script%s>%s</script>', $attributes, $script);
                }
            }

            return implode(PHP_EOL, $result);

        });
    }

    protected function getDataAttributes(AssetInterface $asset)
    {
        $attributes = '';

        foreach ($asset->getOptions() as $name => $value) {
            if ('data-' == substr($name, 0, 5)) {
                $attributes .= sprintf(' %s="%s"', $name, htmlspecialchars($value));
            }
        }

        return $attributes;
    }
}
