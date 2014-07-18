<?php

namespace Pagekit\Component\Session\Handler;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Pagekit\Component\Database\Connection;

class DatabaseSessionHandler implements \SessionHandlerInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $table;

    /**
     * Constructor.
     *
     * @param Connection $connection
     * @param string     $table
     */
    public function __construct(Connection $connection, $table = 'sessions')
    {
        $this->connection = $connection;
        $this->table      = $table;
    }

    /**
     * {@inheritdoc}
     */
    public function open($path = null, $name = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($id)
    {
        try {
            $this->connection->executeQuery("DELETE FROM {$this->table} WHERE id = :id", compact('id'));
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('PDOException was thrown when trying to manipulate session data: %s', $e->getMessage()), 0, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        try {
            $this->connection->executeQuery("DELETE FROM {$this->table} WHERE time < :time", ['time' => date('Y-m-d H:i:s', time() - $lifetime)]);
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('PDOException was thrown when trying to manipulate session data: %s', $e->getMessage()), 0, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($id)
    {
        try {

            $data = $this->connection->executeQuery("SELECT data FROM {$this->table} WHERE id = :id", compact('id'))->fetchColumn();

            if ($data !== false) {
                return base64_decode($data);
            }

            $this->createNewSession($id);

            return '';

        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('PDOException was thrown when trying to read the session data: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($id, $data)
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MySqlPlatform) {
            $sql = "INSERT INTO {$this->table} (id, data, time) VALUES (%1\$s, %2\$s, %3\$s) "
                  ."ON DUPLICATE KEY UPDATE data = VALUES(data), time = CASE WHEN time = %3\$s THEN (VALUES(time) + INTERVAL 1 SECOND) ELSE VALUES(time) END";
        } else {
            $sql = "UPDATE {$this->table} SET data = %2\$s, time = %3\$d WHERE id = %1\$s";
        }

        try {

            $rowCount = $this->connection->exec(sprintf(
                $sql,
                $this->connection->quote($id),
                $this->connection->quote(base64_encode($data)),
                $this->connection->quote(date('Y-m-d H:i:s'))
            ));

            if (!$rowCount) {
                // No session exists in the database to update. This happens when we have called
                // session_regenerate_id()
                $this->createNewSession($id, $data);
            }

        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('PDOException was thrown when trying to write the session data: %s', $e->getMessage()), 0, $e);
        }

        return true;
    }

   /**
    * Creates a new session with the given $id and $data
    *
    * @param  string $id
    * @param  string $data
    * @return bool
    */
    protected function createNewSession($id, $data = '')
    {
        $this->connection->exec(sprintf("INSERT INTO {$this->table} (id, data, time) VALUES (%s, %s, %s)",
            $this->connection->quote($id),
            $this->connection->quote(base64_encode($data)),
            $this->connection->quote(date('Y-m-d H:i:s'))
        ));

        return true;
    }
}
