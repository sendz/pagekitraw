<?php

namespace Pagekit\Component\Database;

use Doctrine\DBAL\DriverManager;
use Pagekit\Component\Database\Query\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Connection
{
    /**
     * The database connection.
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * The database utility.
     *
     * @var Utility
     */
    protected $utility;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    protected $events;

    /**
     * Constructor.
     *
     * @param array                    $params
     * @param EventDispatcherInterface $events
     */
    public function __construct(array $params, EventDispatcherInterface $events = null)
    {
        $this->connection = DriverManager::getConnection($params);
        $this->events     = $events ?: new EventDispatcher;
    }

    /**
     * Gets the database utility.
     *
     * @return string
     */
    public function getUtility()
    {
        if (!$this->utility) {
            $this->utility = new Utility($this);
        }

        return $this->utility;
    }

    /**
     * Gets the event dispatcher.
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Connects to the database.
     *
     * @return boolean
     */
    public function connect()
    {
        if ($connected = $this->connection->connect()) {
            $this->events->dispatch(Events::postConnect, new Event\ConnectionEvent($this));
        }

        return $connected;
    }

    /**
     * Gets the a query builder instance for a table.
     *
     * @param  string $table
     * @return QueryBuilder
     */
    public function table($table)
    {
        return $this->createQueryBuilder()->from($table);
    }

    /**
     * Gets the a query builder instance.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return new Query\QueryBuilder($this);
    }

    /**
     * Proxy method call to database connection.
     *
     * @param  string $method
     * @param  array $args
     * @throws \BadMethodCallException
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!method_exists($this->connection, $method)) {
            throw new \BadMethodCallException(sprintf('Undefined method call "%s::%s"', get_class($this->connection), $method));
        }

        return call_user_func_array([$this->connection, $method], $args);
    }
}
