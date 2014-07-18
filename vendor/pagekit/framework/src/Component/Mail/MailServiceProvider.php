<?php

namespace Pagekit\Component\Mail;

use Pagekit\Framework\Application;
use Pagekit\Framework\ServiceProviderInterface;
use Swift_MailTransport;

class MailServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['mailer'] = function($app) {

            $app['mailer.initialized'] = true;

            return new Mailer($app['swift.transport'], $app['swift.spooltransport']);
        };

        $app['mailer.initialized'] = false;

        $app['swift.transport'] = function($app) {

            $driver = $app['config']->get('mail.driver');

            if ('smtp' == $driver) {

                $transport = new \Swift_Transport_EsmtpTransport(
                    $app['swift.transport.buffer'],
                    [$app['swift.transport.authhandler']],
                    $app['swift.transport.eventdispatcher']
                );

                $options = array_replace([
                    'host'       => 'localhost',
                    'port'       => 25,
                    'username'   => '',
                    'password'   => '',
                    'encryption' => null,
                    'auth_mode'  => null,
                ], $app['config']->get('mail', []));

                $transport->setHost($options['host']);
                $transport->setPort($options['port']);
                $transport->setEncryption($options['encryption']);
                $transport->setUsername($options['username']);
                $transport->setPassword($options['password']);
                $transport->setAuthMode($options['auth_mode']);

                return $transport;
            }

            if ('mail' == $driver) {
                return Swift_MailTransport::newInstance();
            }

            throw new \InvalidArgumentException('Invalid mail driver.');
        };

        $app['swift.transport.buffer'] = function () {
            return new \Swift_Transport_StreamBuffer(new \Swift_StreamFilters_StringReplacementFilterFactory);
        };

        $app['swift.transport.authhandler'] = function () {
            return new \Swift_Transport_Esmtp_AuthHandler([
                new \Swift_Transport_Esmtp_Auth_CramMd5Authenticator,
                new \Swift_Transport_Esmtp_Auth_LoginAuthenticator,
                new \Swift_Transport_Esmtp_Auth_PlainAuthenticator,
            ]);
        };

        $app['swift.transport.eventdispatcher'] = function () {
            return new \Swift_Events_SimpleEventDispatcher;
        };

        $app['swift.spool'] = function () {
            return new \Swift_MemorySpool;
        };

        $app['swift.spooltransport'] = function ($app) {
            return new \Swift_SpoolTransport($app['swift.spool']);
        };
    }

    public function boot(Application $app)
    {
        $app->on('kernel.terminate', function () use ($app) {
            if ($app['mailer.initialized']) {
                try {
                    $app['swift.spooltransport']->getSpool()->flushQueue($app['swift.transport']);
                } catch(\Exception $e) {}
            }
        });
    }
}
