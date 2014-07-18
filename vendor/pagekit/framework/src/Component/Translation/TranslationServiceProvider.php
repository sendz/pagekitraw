<?php

namespace Pagekit\Component\Translation;

use Pagekit\Component\Translation\Loader\MoFileLoader;
use Pagekit\Component\Translation\Loader\PoFileLoader;
use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class TranslationServiceProvider implements ServiceProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    public static $translator;

    public function register(Application $app)
    {
        $app['translator'] = function($app) {

            $translator = new Translator($app['config']['app.locale']);
            $translator->addLoader('mo', new MoFileLoader);
            $translator->addLoader('po', new PoFileLoader);
            $translator->addLoader('array', new ArrayLoader);

            return $translator;
        };
    }

    public function boot(Application $app)
    {
        require __DIR__.'/functions.php';

        self::$translator = $app['translator'];
    }
}
