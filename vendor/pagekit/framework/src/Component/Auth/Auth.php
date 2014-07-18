<?php

namespace Pagekit\Component\Auth;

use Pagekit\Component\Auth\Event\AuthenticateEvent;
use Pagekit\Component\Auth\Event\AuthorizeEvent;
use Pagekit\Component\Auth\Event\LoginEvent;
use Pagekit\Component\Auth\Event\LogoutEvent;
use Pagekit\Component\Auth\Exception\BadCredentialsException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Auth
{
    const USERNAME_PARAM = 'username';
    const REDIRECT_PARAM = 'redirect';
    const LAST_USERNAME  = '_auth.last_username';

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var UserProviderInterface
     */
    protected $provider;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var EventDispatcherInterface
     */
    protected $events;

    /**
     * @var string
     */
    protected $token;

    /**
     * Constructor.
     *
     * @param SessionInterface         $session
     * @param EventDispatcherInterface $events
     */
    public function __construct(EventDispatcherInterface $events, SessionInterface $session = null)
    {
        $this->events  = $events;
        $this->session = $session;
    }

    /**
     * Get a unique identifier for the auth session value.
     *
     * @param  string $var
     * @return string
     */
    public function getName($var = 'user')
    {
        return "_auth.{$var}_".sha1(get_class($this));
    }

    /**
     * Gets the current user
     *
     * @return UserInterface|null
     */
    public function getUser()
    {
        if (null !== $this->user) {
            return $this->user;
        }

        if ($user = $this->session->get($this->getName()) and $user instanceof UserInterface) {

            if ($this->token != $this->session->get($this->getName('token'))) {
                $user = $this->getUserProvider()->find($user->getId());
                $this->session->set($this->getName(), $user);
                $this->session->set($this->getName('token'), $this->token);
            }

            $this->user = $user;
        }

        return $this->user;
    }

    /**
     * Sets the user to be used.
     *
     * @param UserInterface
     */
    public function setUser(UserInterface $user)
    {
        $this->session->set($this->getName(), $user);
        $this->session->set($this->getName('token'), $this->token);
        $this->user = $user;
    }

    /**
     * Gets the user provider.
     *
     * @throws \RuntimeException
     * @return UserProviderInterface
     */
    public function getUserProvider()
    {
        if (!$this->provider) {
            throw new \RuntimeException('Accessed user provider prior to registering it.');
        }

        return $this->provider;
    }

    /**
     * Sets the user provider.
     *
     * @param UserProviderInterface
     */
    public function setUserProvider(UserProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Gets the session.
     *
     * @return SessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Sets the session.
     *
     * @param SessionInterface $session
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Attempts to authenticate the given user according to the passed credentials.
     *
     * @param  array $credentials
     * @return UserInterface
     * @throws BadCredentialsException
     */
    public function authenticate(array $credentials)
    {
        $this->events->dispatch(AuthEvents::PRE_AUTHENTICATE, new AuthenticateEvent($credentials));

        if (!$user = $this->getUserProvider()->findByCredentials($credentials) or !$this->getUserProvider()->validateCredentials($user, $credentials)) {

            $this->session->set(self::LAST_USERNAME, $credentials[self::USERNAME_PARAM]);
            $this->events->dispatch(AuthEvents::FAILURE, new AuthenticateEvent($credentials, $user));

            throw new BadCredentialsException($credentials);
        }

        $this->events->dispatch(AuthEvents::SUCCESS, new AuthenticateEvent($credentials, $user));
        $this->session->remove(self::LAST_USERNAME);

        return $user;
    }

    /**
     * Authorize a user.
     *
     * @param UserInterface $user
     * @throws Exception\AuthException
     */
    public function authorize(UserInterface $user)
    {
        $this->events->dispatch(AuthEvents::AUTHORIZE, $event = new AuthorizeEvent($user));
    }

    /**
     * Log an user into the application.
     *
     * @param UserInterface $user
     * @return Response
     */
    public function login(UserInterface $user)
    {
        $this->session->migrate();

        $this->setUser($user);

        $this->events->dispatch(AuthEvents::LOGIN, $event = new LoginEvent($user));
        return $event->getResponse();
    }

    /**
     * Logs the current user out.
     *
     * @return Response
     */
    public function logout()
    {
        $this->events->dispatch(AuthEvents::LOGOUT, $event = new LogoutEvent($this->user));

        $this->user = null;

        $this->session->invalidate();

        return $event->getResponse();
    }

    /**
     * Sets the token used to identify when to refresh the user from the session
     *
     * @param integer $token
     */
    public function refresh($token = null)
    {
        $this->token = $token;
    }
}
