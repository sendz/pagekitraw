<?php

namespace Pagekit\Component\Profiler\Event;

use Pagekit\Component\Routing\Controller\ControllerCollection;
use Pagekit\Component\Routing\UrlProvider;
use Pagekit\Component\View\ViewInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ToolbarListener implements EventSubscriberInterface
{
    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var UrlProvider
     */
    protected $url;

    /**
     * @var ViewInterface
     */
    protected $view;

    /**
     * @var ControllerCollection
     */
    protected $controllers;

    /**
     * Constructor.
     *
     * @param Profiler             $profiler
     * @param ViewInterface        $view
     * @param UrlProvider          $url
     * @param ControllerCollection $controllers
     */
    public function __construct(Profiler $profiler, ViewInterface $view, UrlProvider $url, ControllerCollection $controllers)
    {
        $this->profiler    = $profiler;
        $this->view        = $view;
        $this->url         = $url;
        $this->controllers = $controllers;
    }

    public function onKernelRequest()
    {
        $this->controllers->get('_profiler/{token}', '_profiler', function($token) {

            if (!$profile = $this->profiler->loadProfile($token)) {
                return new Response;
            }

            return new Response($this->view->render(__DIR__.'/../views/toolbar.php', ['profiler' => $this->profiler, 'profile' => $profile, 'token' => $token]));

        })->setDefault('_maintenance', true);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $request  = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if ($request->isXmlHttpRequest()) {
            return;
        }

        if ($request->attributes->get('_disable_profiler_toolbar')
                || !$response->headers->has('X-Debug-Token')
                || $response->isRedirection()
                || ($response->headers->has('Content-Type') && false === strpos($response->headers->get('Content-Type'), 'html'))
                || 'html' !== $request->getRequestFormat()
        ) {
            return;
        }

        $this->injectToolbar($response);
    }

    /**
     * Injects the web debug toolbar into the given Response.
     *
     * @param Response $response A Response instance
     */
    protected function injectToolbar(Response $response)
    {
        if (function_exists('mb_stripos')) {
            $posrFunction   = 'mb_strripos';
            $substrFunction = 'mb_substr';
        } else {
            $posrFunction   = 'strripos';
            $substrFunction = 'substr';
        }

        $content  = $response->getContent();
        $token    = $response->headers->get('X-Debug-Token');
        $route    = $this->url->route('_profiler', compact('token'));
        $url      = $this->url->to(__DIR__.'/../assets');
        $markup[] = "<div id=\"profiler\" data-url=\"$url\" data-route=\"$route\" style=\"display: none\"></div>";
        $markup[] = "<script src=\"$url/js/profiler.js\"></script>";

        if (false !== $pos = $posrFunction($content, '</body>')) {
            $content = $substrFunction($content, 0, $pos).implode("\n", $markup).$substrFunction($content, $pos);
            $response->setContent($content);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => ['onKernelResponse', -100]
        ];
    }
}
