<?php

namespace Pagekit\Component\Database\ORM;

use Pagekit\Component\Database\Connection;
use Pagekit\Component\Database\Event\EntityEvent;
use Pagekit\Component\Database\Events;

class EntityManager
{
    const STATE_MANAGED  = 1;
    const STATE_NEW      = 2;
    const STATE_DETACHED = 3;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var MetadataManager
     */
    protected $metadata;

    /**
     * @var Repository[]
     */
    protected $repositories = [];

    /**
     * @var EntityMap
     */
    protected $entities;

    /**
     * @var string
     */
    protected $eventClass;

    /**
     * Creates a new Manager instance
     *
     * @param  Connection      $connection
     * @param  MetadataManager $metadata
     * @param  string          $eventClass
     * @throws \RuntimeException
     */
    public function __construct($connection, MetadataManager $metadata, $eventClass = 'Pagekit\Component\Database\Event\EntityEvent')
    {
        $this->connection = $connection;
        $this->metadata   = $metadata;

        if (!is_subclass_of($eventClass, 'Pagekit\Component\Database\Event\EntityEvent')) {
            throw new \RuntimeException(sprintf('The Event Class %s is not a subclass of "Pagekit\Component\Database\Event\EntityEvent"', $eventClass));
        }

        $this->eventClass = $eventClass;
        $this->entities   = new EntityMap($this);
    }

    /**
     * Gets the database connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Gets the metadata object of an entity class.
     *
     * @param  mixed $class
     * @return Metadata
     */
    public function getMetadata($class)
    {
        return $this->metadata->get($class);
    }

    /**
     * Gets the metadata manager.
     *
     * @return MetadataManager
     */
    public function getMetadataManager()
    {
        return $this->metadata;
    }

    /**
     * Returns the repository for an entity class.
     *
     * @param  string $entity
     * @return Repository
     */
    public function getRepository($entity)
    {
        $entity = ltrim($entity, '\\');

        if (!isset($this->repositories[$entity])) {

            $metadata   = $this->getMetadata($entity);
            $repository = $metadata->getRepositoryClass() ?: 'Pagekit\Component\Database\ORM\Repository';

            $this->repositories[$entity] = new $repository($this, $metadata);
        }

        return $this->repositories[$entity];
    }

    /**
     * Retrieve an entity by its identifier.
     *
     * @param  string $entity
     * @param  mixed  $identifier
     * @return mixed
     */
    public function find($entity, $identifier)
    {
        return $this->getRepository($entity)->find($identifier);
    }

    /**
     * Checks whether the given managed entity exists in the database.
     *
     * @param  object $entity
     * @return bool
     */
    public function exists($entity)
    {
        $metadata   = $this->getMetadata($entity);
        $identifier = $metadata->getIdentifier(true);

        if (empty($identifier)) {
            return false;
        }

        return (bool) $this->connection->fetchColumn('SELECT 1 FROM '.$metadata->getTable().' WHERE '.$identifier.'='.$this->connection->quote($metadata->getValue($entity, $identifier, true)));
    }

    /**
     * Gets the state of an entity.
     *
     * @param  object  $entity
     * @param  integer $assume
     * @return int
     */
    public function getEntityState($entity, $assume = null)
    {
        if ($this->entities->has($entity)) {
            return self::STATE_MANAGED;
        }

        if ($assume !== null) {
            return $assume;
        }

        $metadata   = $this->getMetadata($entity);
        $identifier = $metadata->getIdentifier();

        return !$metadata->getValue($entity, $identifier) ? self::STATE_NEW : self::STATE_DETACHED;
    }

    /**
     * {@see EntityMap::get}
     */
    public function getById($id, $class)
    {
        $this->entities->get($id, $class);
    }

    /**
     * Relate target entities to the entity's relation.
     *
     * @param  array        $entities
     * @param  string       $name
     * @param  QueryBuilder $query
     * @throws \LogicException
     */
    public function related($entities, $name, QueryBuilder $query)
    {
        if (!is_array($entities)) {
            $entities = [$entities];
        }

        $metadata = $this->getMetadata(current($entities));
        $mapping  = $metadata->getRelationMapping($name);

        if (!class_exists($class = 'Pagekit\Component\Database\ORM\\Relation\\'.$mapping['type'])) {
            throw new \LogicException(sprintf("Unable to find relation class '%s'", $class));
        }

        $relation = new $class($this, $metadata, $mapping);
        $relation->resolve($entities, $query);
    }

    /**
     * Saves an entity.
     *
     * @param object $entity
     * @param array  $data
     */
    public function save($entity, array $data = [])
    {
        $metadata = $this->getMetadata($entity);
        $identifier = $metadata->getIdentifier(true);

        $metadata->setValues($entity, $data);

        $this->dispatchEvent(Events::preSave, $entity, $metadata);

        switch ($this->getEntityState($entity, self::STATE_NEW)) {

            case self::STATE_NEW:

                $this->dispatchEvent(Events::preCreate, $entity, $metadata);

                $this->connection->insert($metadata->getTable(), $metadata->getValues($entity, true, true));
                $this->entities->add($entity, $id = $this->connection->lastInsertId());

                $metadata->setValue($entity, $identifier, $id, true);

                $this->dispatchEvent(Events::postCreate, $entity, $metadata);

                break;

            case self::STATE_MANAGED:

                $this->dispatchEvent(Events::preUpdate, $entity, $metadata);

                $values = $metadata->getValues($entity, true, true);
                $this->connection->update($metadata->getTable(), $values, [$identifier => $values[$identifier]]);

                $this->dispatchEvent(Events::postUpdate, $entity, $metadata);
        }

        $this->dispatchEvent(Events::postSave, $entity, $metadata);
    }

    /**
     * Deletes an entity.
     *
     * @param  object $entity
     * @throws \InvalidArgumentException
     */
    public function delete($entity)
    {
        $metadata = $this->getMetadata($entity);
        $identifier = $metadata->getIdentifier(true);

        switch ($state = $this->getEntityState($entity)) {

            case self::STATE_MANAGED:

                $this->dispatchEvent(Events::preDelete, $entity, $metadata);

                if (!$value = $metadata->getValue($entity, $identifier, true)) {
                    throw new \InvalidArgumentException("Can't remove entity with empty identifier value.");
                }

                $this->connection->delete($metadata->getTable(), [$identifier => $value]);
                $this->entities->remove($entity);

                $this->dispatchEvent(Events::postDelete, $entity, $metadata);

                $metadata->setValue($entity, $identifier, null, true);

                break;

            case self::STATE_DETACHED:
                throw new \InvalidArgumentException("Detached entity can not be removed");

            default:
                throw new \InvalidArgumentException(sprintf("Unexpected entity state: %s.", $state));
        }
    }

    /**
     * Hydrates only one row of the passed statement.
     *
     * @param  object   $statement
     * @param  Metadata $metadata
     * @return mixed
     */
    public function hydrateOne($statement, Metadata $metadata)
    {
        if ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            return $this->entities->load($metadata, $row);
        }

        return false;
    }

    /**
     * Hydrates all rows returned by the passed statement instance at once.
     *
     * @param  object   $statement
     * @param  Metadata $metadata
     * @return mixed
     */
    public function hydrateAll($statement, Metadata $metadata)
    {
        $result = [];
        $identifier = $metadata->getIdentifier();

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $entity = $this->entities->load($metadata, $row);
            $result[$metadata->getValue($entity, $identifier)] = $entity;
        }

        return $result;
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param  string   $name
     * @param  mixed    $entity
     * @param  Metadata $metadata
     * @return bool
     */
    public function dispatchEvent($name, $entity, Metadata $metadata)
    {
        $prefix = $metadata->getEventPrefix();
        $event  = new $this->eventClass($entity, $metadata, $this);

        if ($events = $metadata->getEvents() and isset($events[$name])) {
            foreach ($events[$name] as $callback) {
                call_user_func_array([$entity, $callback], [$event]);
            }
        }

        $this->connection->getEventDispatcher()->dispatch(($prefix ? $prefix.'.' : '').$name, $event);
    }
}
