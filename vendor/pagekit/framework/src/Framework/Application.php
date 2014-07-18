<?php

namespace Pagekit\Framework;

use Pagekit\Framework\Exception\ExceptionListenerWrapper;
use Pagekit\Framework\Provider\EventServiceProvider;
use Pagekit\Framework\Provider\RoutingServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\TerminableInterface;

class Application extends \Pimple implements HttpKernelInterface, TerminableInterface
{
    const EARLY_EVENT = 512;
    const LATE_EVENT  = -512;

    protected $providers = [];
    protected $booted = false;

    /**
     * Constructor.
     *
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        parent::__construct();

        $this['app'] = $this;

        $this->register(new EventServiceProvider);
        $this->register(new RoutingServiceProvider);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        if (version_compare(PHP_VERSION, '5.4', '>=')) {
            ApplicationTrait::setApplication($this);
        }
    }

    /**
     * Registers a service provider.
     *
     * @param  ServiceProviderInterface|string $provider
     * @param  array                           $values
     * @throws \InvalidArgumentException
     * @return Application
     */
    public function register($provider, array $values = [])
    {
        if (is_string($provider)) {
            $provider = new $provider;
        }

        if (!$provider instanceof ServiceProviderInterface) {
            throw new \InvalidArgumentException('Provider must implement the ServiceProviderInterface.');
        }

        $this->providers[] = $provider;

        $provider->register($this);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }

    /**
     * Boots all service providers.
     *
     * This method is automatically called by handle(), but you can use it
     * to boot all service providers when not handling a request.
     */
    public function boot()
    {
        if (!$this->booted) {

            foreach ($this->providers as $provider) {
                $provider->boot($this);
            }

            $this->booted = true;
        }
    }

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string $event
     * @param mixed  $callback
     * @param int    $priority
     */
    public function on($event, $callback, $priority = 0)
    {
        $this['events']->addListener($event, $callback, $priority);
    }

    /**
     * Aborts the current request by sending a proper HTTP error.
     *
     * @param  int    $code
     * @param  string $message
     * @param  array  $headers
     * @throws HttpException
     * @throws NotFoundHttpException
     */
    public function abort($code, $message = '', array $headers = [])
    {
        $this['router']->abort($code, $message, $headers);
    }

    /**
     * Registers an error handler.
     *
     * @param mixed   $callback
     * @param integer $priority
     */
    public function error($callback, $priority = -8)
    {
        $this->on(KernelEvents::EXCEPTION, new ExceptionListenerWrapper($this, $callback), $priority);
    }

    /**
     * Handles the request and delivers the response.
     *
     * @param Request $request
     */
    public function run(Request $request = null)
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        $response = $this->handle($request);
        $response->send();

        $this->terminate($request, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (!$this->booted) {
            $this->boot();
        }

        $this['request'] = $request;

        return $this['router']->handle($request, $type, $catch);
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        $this['router']->terminate($request, $response);
    }

    /**
     * Determine if we are running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return PHP_SAPI == 'cli';
    }
}
