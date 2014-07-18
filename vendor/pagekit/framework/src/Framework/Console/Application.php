<?php

namespace Pagekit\Framework\Console;

use Pagekit\Framework\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Application as BaseAppliction;
use Symfony\Component\Console\Command\Command as BaseCommand;

class Application extends BaseAppliction
{
    /**
     * The Pagekit application instance.
     *
     * @var Application
     */
    protected $pagekit;

    /**
     * Constructor.
     *
     * @param Application $pagekit
     * @param string      $name
     */
    public function __construct($pagekit, $name = null)
    {
        parent::__construct($name ?: 'Pagekit', $pagekit['config']['app.version']);

        $this->pagekit = $pagekit;

        if (isset($pagekit['events'])) {
            $pagekit['events']->dispatch('console.init', new ConsoleEvent($this));
        }
    }

    /**
     * Add a command to the console.
     *
     * @param  BaseCommand $command
     * @return BaseCommand
     */
    public function add(BaseCommand $command)
    {
        if ($command instanceof Command) {
            $command->setPagekit($this->pagekit);
        }

        return parent::add($command);
    }
}
