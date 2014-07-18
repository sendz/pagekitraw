<?php

namespace Pagekit\Component\File\Adapter;

class FtpAdapter implements AdapterInterface
{
    protected $connection;
    protected $directory;
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $passive;
    protected $mode;
    protected $fileData = [];

    /**
     * Constructor
     *
     * @param  string $directory The directory to use in the ftp server
     * @param  string $host The host of the ftp server
     * @param  string $username The username
     * @param  string $password The password
     * @param  int|string $port The ftp port (default 21)
     * @param  bool|string $passive Whether to switch the ftp connection in passive mode
     * @param  int|string $mode Transfer-Mode (FTP_ASCII oder FTP_BINARY)
     * @throws \RuntimeException
     *
     * TODO: replace these arguments by an array of options
     * TODO: use FTP_BINARY by default
     */
    public function __construct($directory, $host, $username = 'anonymous', $password = '', $port = 21, $passive = false, $mode = FTP_ASCII)
    {
		if (!extension_loaded('ftp')) {
            throw new \RuntimeException('PHP ftp extension not loaded.');
		}

        $this->directory = str_replace('\\', '/', (string) $directory);
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->passive = $passive;
        $this->mode = $mode;
    }

	public function __destruct() {
		if ($this->connection) {
			ftp_close($this->connection);
        }
	}

    /**
     * {@inheritdoc}
     */
    public function chmod($file, $mode, $umask = 0000)
    {
        if (true !== @ftp_chmod($this->getConnection(), $mode & ~$umask, $this->computePath($file))) {
            throw new IOException(sprintf('Failed to chmod file %s', $file));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function chown($file, $user)
    {
        throw new IOException('CHOWN not supported for ftp adapter');
    }

    /**
     * {@inheritdoc}
     */
    public function chgrp($file, $group)
    {
        throw new IOException('CHGRP not supported for ftp adapter');
    }

    /**
     * {@inheritdoc}
     */
    public function read($file)
    {
        $temp = fopen('php://temp', 'r+');

        if (true !== @ftp_fget($this->getConnection(), $temp, $this->computePath($file), $this->mode)) {
            throw new IOException(sprintf('Failed to read the file %s', $file));
        }

        rewind($temp);
        $contents = stream_get_contents($temp);
        fclose($temp);

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function write($file, $data, $append)
    {
        $path = $this->computePath($file);
        $directory = dirname($path);

        $this->ensureDirExists($directory);

        $startpos = 0;
        if ($append && $this->exists($file) and $size = $this->size($file) and $size > 0) {
            $startpos = $size;
            $data = str_pad($data, $startpos + strlen($data), ' ', STR_PAD_LEFT);
        }

        $temp = fopen('php://temp', 'r+');
        $size = fwrite($temp, $data);
        rewind($temp);

        if (true !== @ftp_fput($this->getConnection(), $path, $temp, $this->mode, $startpos)) {
            throw new IOException(sprintf('Failed to write to file %s', $file));
        }

        fclose($temp);

        return $size;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($file)
    {
        $file  = $this->computePath($file);
        if (false === $items = @ftp_nlist($this->getConnection(), dirname($file))) {
            throw new IOException(sprintf('Failed to nlist directory %s', dirname($file)));
        }

        return in_array($file, $items);
    }

    /**
     * {@inheritdoc}
     */
    public function rmdir($dir)
    {
        if (true !== @ftp_rmdir($this->getConnection(), $this->computePath($dir))) {
            throw new IOException(sprintf('Failed to delete directory %s', $dir));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unlink($file)
    {
        if (true !== @ftp_delete($this->getConnection(), $this->computePath($file))) {
            throw new IOException(sprintf('Failed to delete file %s', $file));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename($oldname, $newname)
    {
        if (true !== @ftp_rename($this->getConnection(), $this->computePath($oldname), $this->computePath($newname))) {
            throw new IOException(sprintf('Failed to rename file %s to %s', $oldname, $newname));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir($dir, $mode = 0777, $recursive = false)
    {
        $dir = $this->computePath($dir);

        // create parent directory if needed
        $parent = dirname($dir);
        if (!$this->isDir($parent)) {
            if (!$recursive) {
                throw new IOException(sprintf('Could not create directory %s. Parent directory does not exist.', $dir));
            }
            $this->mkdir($parent, $mode, $recursive);
        }

        // create the specified directory
        if (false === @ftp_mkdir($this->getConnection(), $dir)) {
            throw new IOException(sprintf('Failed to create directory %s', $dir));
        }

        $this->chmod($dir, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($source, $dest)
    {
        $temp = fopen('php://temp', 'r+');

        if (true !== @ftp_fget($this->getConnection(), $temp, $this->computePath($source), $this->mode)) {
            throw new IOException(sprintf('Failed to copy the file %s', $source));
        }

        rewind($temp);

        if (true !== @ftp_fput($this->getConnection(), $this->computePath($dest), $temp, $this->mode)) {
            throw new IOException(sprintf('Failed to copy the file %s', $source));
        }
    }

    /**
     * {@inheritdoc}
     */
	public function mtime($file)
    {
        if (-1 === $time = @ftp_mdtm($this->getConnection(), $this->computePath($file))) {
            throw new IOException(sprintf('Failed to get file modification time for %s', $file));
        }
        return $time;
    }

	/**
	 * Get the file size of a given file.
	 *
	 * @param  string  $file
     *
	 * @return int
     *
     * @throws IOException if the filename cannot be read.
	 */
	public function size($file)
    {
        if (-1 === $size = @ftp_size($this->getConnection(), $this->computePath($file))) {
            throw new IOException(sprintf('Failed to get file size for %s', $file));
        }
        return $size;
    }

    /**
     * Lists files from the specified directory.
     *
     * @param  string $directory The path of the directory to list from
     *
     * @return array An array of files and dirs
     */
    public function lsdir($directory = '')
    {
        $directory = $this->computePath($directory);

        $files = $dirs = [];

        $items = $this->parseRawlist(ftp_rawlist($this->getConnection(), $directory ) ? : []);

        foreach ($items as $itemData) {

            if ('..' === $itemData['name'] || '.' === $itemData['name']) {
                continue;
            }
            $path = trim($itemData['name'], '/');

            if ('-' === substr($itemData['perms'], 0, 1)) {
                $files[] = $path;
            } elseif('d' === substr($itemData['perms'], 0, 1)) {
                $dirs[] = $path;
            }
        }

        return [
           'files'   => $files,
           'dirs'   => $dirs
        ];
    }

    /**
     * Computes the path for the given file
     *
     * @param  string $file
     */
    protected function computePath($file)
    {
        $file = str_replace('\\', '/', $file);

        if (!$this->isAbsolutePath($file)) {
            $file = rtrim($this->directory, '/') . '/' . $file;
        }

        return $file;
    }

    /**
     * Returns an opened ftp connection resource. If the connection is not already opened, it open it before
     *
     * @return resource The ftp connection
     */
    public function getConnection()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * Opens the adapter's ftp connection
     *
     * @throws RuntimeException if could not connect
     */
    protected function connect()
    {
        // open ftp connection
        $this->connection = @ftp_connect($this->host, $this->port);
        if (!$this->connection) {
            throw new IOException(sprintf('Could not connect to \'%s\' (port: %s).', $this->host, $this->port));
        }

        $username = $this->username ? : 'anonymous';
        $password = $this->password ? : '';

        // login ftp user
        if (!ftp_login($this->connection, $username, $password)) {
            $this->close();
            throw new IOException(sprintf('Could not login as %s.', $this->username));
        }

        // switch to passive mode if needed
        if ($this->passive && !ftp_pasv($this->connection, true)) {
            $this->close();
            throw new IOException('Could not turn passive mode on.');
        }

        if ('' === $this->directory) {
            $this->directory = ftp_pwd($this->connection);
        }

        // ensure the adapter's directory exists
        if ('/' !== $this->directory) {
            try {
                $this->ensureDirExists($this->directory);
            } catch (IOException $e) {
                $this->close();
                throw $e;
            }

            // change the current directory for the adapter's directory
            if (!ftp_chdir($this->connection, $this->directory)) {
                $this->close();
                throw new IOException(sprintf('Could not change current directory for the \'%s\' directory.', $this->directory));
            }
        }
    }

    /**
     * Indicates whether the adapter has an open ftp connection
     *
     * @return boolean
     */
    protected function isConnected()
    {
        return is_resource($this->connection);
    }

    /**
     * Closes the adapter's ftp connection
     */
    protected function close()
    {
        if ($this->isConnected()) {
            ftp_close($this->connection);
        }
    }

    /**
     * Returns whether the file path is an absolute path.
     *
     * @param string $file A file path
     *
     * @return Boolean
     */
    public function isAbsolutePath($file)
    {
        return strspn($file, '/\\', 0, 1);
    }

    /**
     * Ensures the specified directory exists, creates it if it does not
     *
     * @param  string  $directory Path of the directory to test
     *
     * @throws IOException If the directory does not exists and could not be created
     */
    public function ensuredir($dir)
    {
        if (!$this->isDir($dir)) {
            if ($this->exists($dir)) {
                throw new IOException($dir.' exists and is not a directory.');
            }
            $this->mkdir($dir, 0777, true);
        }
    }

    /**
     * Indicates whether the specified directory exists
     *
     * @param  string $directory
     *
     * @return boolean TRUE if the directory exists, FALSE otherwise
     */
    public function isDir($directory)
    {
        if ('/' === $directory) {
            return true;
        }

        if (!@ftp_chdir($this->getConnection(), $this->computePath($directory))) {
            return false;
        }

        // change directory back to base directory
        ftp_chdir($this->getConnection(), $this->directory);

        return true;
    }

    /**
     * Parses the given raw list
     *
     * @param  array $rawlist
     *
     * @return array
     */
    protected function parseRawlist(array $rawlist)
    {
        $parsed = [];
        foreach ($rawlist as $line) {
            $infos = preg_split("/[\s]+/", $line, 9);
            $infos[7] = (strrpos($infos[7], ':') != 2 ) ? ($infos[7] . ' 00:00') : (date('Y') . ' ' . $infos[7]);

            if ('total' !== $infos[0]) {
                $parsed[] = [
                    'perms' => $infos[0],
                    'num'   => $infos[1],
                    'size'  => $infos[4],
                    'time'  => strtotime($infos[5] . ' ' . $infos[6] . '. ' . $infos[7]),
                    'name'  => $infos[8]
                ];
            }
        }

        return $parsed;
    }
}