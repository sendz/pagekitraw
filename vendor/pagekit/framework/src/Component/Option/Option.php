<?php

namespace Pagekit\Component\Option;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Pagekit\Component\Cache\CacheInterface;
use Pagekit\Component\Database\Connection;

class Option
{
    /**
     * @var Connection $connection
     */
    protected $connection;

    /**
     * @var CacheInterface $cache
     */
    protected $cache;

    /**
     * The cache prefix
     *
     * @var string $prefix
     */
    protected $prefix = 'Options:';

    /**
     * @var array $ignore
     */
    protected $ignore = [];

    /**
     * @var array $autoload
     */
    protected $autoload = [];

    /**
     * @var array $options
     */
    protected $options = [];

    /**
     * @var array $protected
     */
    protected $protected = ['Ignore', 'Autoload'];

	/**
	 * The name of the options table.
	 *
	 * @var string
	 */
	protected $table;

    /**
     * Constructor.
     *
     * @param Connection     $connection
     * @param CacheInterface $cache
     * @param string         $table
     */
    public function __construct(Connection $connection, CacheInterface $cache, $table)
    {
        $this->connection = $connection;
        $this->cache      = $cache;
        $this->table      = $table;
    }

    /**
     * Gets an option value.
     *
     * @param  string $name
     * @param  mixed $default
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $name = trim($name);

        if (empty($name)) {
            throw new \InvalidArgumentException('Empty option name given.');
        }

        if (empty($this->ignore) && $ignore = $this->cache->fetch($this->prefix.'Ignore')) {
            $this->ignore = $ignore ?: [];
        }

        if (isset($this->ignore[$name])) {
            return $default;
        }

        if (empty($this->autoload)) {

            if ($options = $this->cache->fetch($this->prefix.'Autoload')) {
                $this->autoload = $options;
            }

            if (empty($this->autoload) && $options = $this->connection->fetchAll("SELECT name, value FROM {$this->table} WHERE autoload = 1")) {

                foreach ($options as $option) {
                    $this->autoload[$option['name']] = json_decode($option['value'], true);
                }

                $this->cache->save($this->prefix.'Autoload', $this->autoload);
            }

            if (!empty($this->autoload)) {
                $this->options = $this->autoload;
            }

        }

        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        if ($option = $this->cache->fetch($this->prefix.$name)) {
            return $this->options[$name] = json_decode($option, true);
        }

        if ($option = $this->connection->fetchAssoc("SELECT value FROM {$this->table} WHERE name = ?", [$name])) {
            $this->cache->save($this->prefix.$name, $option['value']);
            return $this->options[$name] = json_decode($option['value'], true);
        }

        $this->ignore[$name] = true;
        $this->cache->save($this->prefix.'Ignore', $this->ignore);

        return $default;
    }

    /**
     * Sets an option value.
     *
     * @param  string $name
     * @param  mixed $value
     * @param  boolean $autoload
     * @throws \InvalidArgumentException
     */
    public function set($name, $value, $autoload = null)
    {
        $name = trim($name);

        if (empty($name)) {
            throw new \InvalidArgumentException('Empty option name given.');
        }

        if (in_array($name, $this->protected)) {
            throw new \InvalidArgumentException(sprintf('"%s" is a protected option and may not be modified.', $name));
        }

        $old_value = $this->get($name);

        if ($value !== $old_value) {

            $this->options[$name] = $value;

            $data = ['name' => $name, 'value' => json_encode($value)];

            if ($autoload !== null) {
                $data['autoload'] = $autoload ? '1' : '0';
            }

            if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {

                if ($autoload === null) {
                    $query = "INSERT INTO {$this->table} (name, value) VALUES (:name, :value) ON DUPLICATE KEY UPDATE value = :value";
                } else {
                    $query = "INSERT INTO {$this->table} (name, value, autoload) VALUES (:name, :value, :autoload) ON DUPLICATE KEY UPDATE value = :value, autoload = :autoload";
                }

                $this->connection->executeQuery($query, $data);

            } elseif (!$this->connection->update($this->table, $data, compact('name'))) {

                $this->connection->insert($this->table, $data);

            }

            $this->cache->delete($this->prefix.(isset($this->autoload[$name]) ? 'Autoload' : $name));
        }

        if (isset($this->ignore[$name])) {
            unset($this->ignore[$name]);
            $this->cache->save($this->prefix.'Ignore', $this->ignore);
        }
    }

    /**
     * Removes a stored option.
     *
     * @param  string $name The name of the option to be removed.
     * @throws \InvalidArgumentException
     */
    public function remove($name)
    {
        $name = trim($name);

        if (empty($name)) {
            throw new \InvalidArgumentException('Empty option name given.');
        }

        if (in_array($name, $this->protected)) {
            throw new \InvalidArgumentException(sprintf('"%s" is a protected option and may not be modified.', $name));
        }

        if ($option = $this->connection->fetchAssoc("SELECT id, autoload FROM {$this->table} WHERE name = ?", [$name])) {
            if ($this->connection->delete($this->table, ['id' => $option['id']])) {
                unset($this->options[$name]);
                $this->cache->delete($this->prefix.($option['autoload'] ? 'Autoload' : $name));
            }
        }
    }
}
