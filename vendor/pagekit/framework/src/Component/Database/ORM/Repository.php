<?php

namespace Pagekit\Component\Database\ORM;

use Pagekit\Component\Database\Connection;

class Repository
{
    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * Creates a new Repository.
     *
     * @param EntityManager $manager
     * @param Metadata      $metadata
     */
    public function __construct(EntityManager $manager, Metadata $metadata)
    {
        $this->manager    = $manager;
        $this->connection = $manager->getConnection();
        $this->metadata   = $metadata;
    }

    /**
     * Gets the related Manager object.
     *
     * @return EntityManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Gets the related Metadata object with mapping information of the class.
     *
     * @return Metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Create a new QueryBuilder instance.
     *
     * @return QueryBuilder
     */
    public function query()
    {
        return new QueryBuilder($this);
    }

    /**
     * Create a new QueryBuilder instance and set the WHERE condition.
     *
     * @param  mixed $condition
     * @param  array $params
     * @return QueryBuilder
     */
    public function where($condition, array $params = [])
    {
        return $this->query()->where($condition, $params);
    }

    /**
     * Retrieve an entity by its identifier.
     *
     * @param  mixed $id
     * @return mixed
     * @throws \Exception
     */
    public function find($id)
    {
        if ($entity = $this->manager->getById($id, $this->metadata->getClass())) {
            return $entity;
        }

        return $this->where([$this->metadata->getIdentifier() => $id])->first();
    }

    /**
     * Retrieve all entities.
     *
     * @return mixed
     */
    public function findAll()
    {
        return $this->query()->get();
    }

    /**
     * Saves an entity.
     *
     * @param object $entity
     * @param array  $data
     */
    public function save($entity, array $data = [])
    {
        $this->manager->save($entity, $data);
    }

    /**
     * Deletes an entity.
     *
     * @param object $entity
     */
    public function delete($entity)
    {
        $this->manager->delete($entity);
    }
}
