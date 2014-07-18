<?php

namespace Pagekit\Component\Session\Csrf\Event;

use Pagekit\Component\Session\Csrf\Exception\BadTokenException;
use Pagekit\Component\Session\Csrf\Provider\CsrfProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class CsrfListener implements EventSubscriberInterface
{
    /**
     * @var CsrfProviderInterface
     */
    protected $provider;

    /**
     * Constructor.
     *
     * @param CsrfProviderInterface $provider
     */
    public function __construct(CsrfProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Checks for the CSRF token and throws 401 exception if invalid.
     *
     * @param GetResponseEvent $event
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($csrf = $request->attributes->get('_request[csrf]', false, true)) {
            if (!$this->provider->validate($request->get(is_string($csrf) ? $csrf : '_csrf'))) {
                throw new BadTokenException(401, 'Invalid CSRF token.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => ['onKernelRequest', -10]
        ];
    }
}
