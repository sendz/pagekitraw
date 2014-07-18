<?php

namespace Pagekit\Framework\Console\Event;

use Pagekit\Framework\Console\Application;
use Pagekit\Framework\Event\Event;

class ConsoleEvent extends Event
{
    /**
     * @var Application
     */
    protected $console;

    /**
     * Constructor.
     *
     * @param Application $console
     */
    public function __construct(Application $console)
    {
        $this->console = $console;
    }

    /**
     * Returns the console application.
     *
     * @return Application
     */
    public function getConsole()
    {
        return $this->console;
    }
}
