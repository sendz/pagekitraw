<?php

namespace Pagekit\Framework\Provider;

use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;
use Pagekit\Framework\Templating\Helper\GravatarHelper;
use Pagekit\Framework\Templating\Helper\MarkdownHelper;
use Pagekit\Framework\Templating\Helper\ScriptHelper;
use Pagekit\Framework\Templating\Helper\SectionHelper;
use Pagekit\Framework\Templating\Helper\StyleHelper;
use Pagekit\Framework\Templating\Helper\TokenHelper;
use Pagekit\Framework\Templating\Section\DelayedRenderer;
use Pagekit\Framework\Templating\TemplateNameParser;
use Symfony\Component\Templating\DelegatingEngine;
use Symfony\Component\Templating\Helper\SlotsHelper;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;

class TemplatingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['tmpl'] = function() {
            return new DelegatingEngine;
        };

        $app['tmpl.parser'] = function($app) {

            $parser = new TemplateNameParser($app['events']);
            $parser->addEngine('php', '.php');

            return $parser;
        };

        $app['tmpl.php'] = function($app) {

            $helpers = [new SlotsHelper, new GravatarHelper];

            if (isset($app['view.styles'])) {
                $helpers[] = new StyleHelper($app['view.styles']);
            }

            if (isset($app['view.scripts'])) {
                $helpers[] = new ScriptHelper($app['view.scripts']);
            }

            if (isset($app['view.sections'])) {
                $helpers[] = new SectionHelper($app['view.sections']);
            }

            if (isset($app['csrf'])) {
                $helpers[] = new TokenHelper($app['csrf']);
            }

            if (isset($app['markdown'])) {
                $helpers[] = new MarkdownHelper($app['markdown']);
            }

            $engine = new PhpEngine($app['tmpl.parser'], new FilesystemLoader([]));
            $engine->addHelpers($helpers);

            return $engine;
        };
    }

    public function boot(Application $app)
    {
        $app['tmpl']->addEngine($app['tmpl.php']);
        $app['view.sections']->addRenderer('delayed', new DelayedRenderer($app['events']));
    }
}
