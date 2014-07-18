<?php

namespace Pagekit\Component\File\Adapter;

interface AdapterInterface
{
    /**
     * Change mode for an array of files or directories.
     *
     * @param  string $file
     * @param  int    $mode
     * @param  int    $umask
     * @return bool
     */
    public function chmod($file, $mode, $umask = 0000);

    /**
     * Change the owner of an array of files or directories
     *
     * @param  string $file
     * @param  string $user
     * @return bool
     */
    public function chown($file, $user);

    /**
     * Change the group of an array of files or directories
     *
     * @param  string $file
     * @param  string $group
     * @return bool
     */
    public function chgrp($file, $group);

    /**
     * Reads the content of the file
     *
     * @param  string $file
     * @return string|false
     */
    public function read($file);

    /**
     * Writes the given data into the file
     *
     * @param  string $file
     * @param  string $data
     * @param  bool   $append
     * @return int|false
     */
    public function write($file, $data, $append);

    /**
     * Indicates whether the file exists
     *
     * @param  string $file
     * @return bool
     */
    public function exists($file);

    /**
     * Deletes the directory
     *
     * @param  string $dir
     * @return bool
     */
    public function rmdir($dir);

    /**
     * Deletes the file
     *
     * @param  string $file
     * @return bool
     */
    public function unlink($file);

    /**
     * Renames a file or directory
     *
     * @param  string $oldname
     * @param  string $newname
     * @return bool
     */
    public function rename($oldname, $newname);

    /**
     * Copies the file.
     *
     * @param  string $source
     * @param  string $dest
     * @return bool
     */
    public function copy($source, $dest);

	/**
	 * Get the file size of a given file.
	 *
	 * @param  string  $file
	 * @return int|false
	 */
	public function size($file);

	/**
	 * Get a file's or directory's modified timestamp.
	 *
	 * @param  string $file
	 * @return int|false
	 */
	public function mtime($file);

    /**
     * Indicates whether the specified directory exists
     *
     * @param  string $dir
     * @return bool
     */
    public function isdir($dir);

    /**
     * Lists files from the specified directory.
     *
     * @param  string $dir
     * @return array  An array of files and dirs
     */
    public function lsdir($dir = '');

     /**
      * Creates a directory.
      *
      * @param  string $dir
      * @param  int    $mode
      * @param  bool   $recursive
      * @return bool
      */
    public function mkdir($dir, $mode = 0777, $recursive = false);

    /**
     * Ensures the specified directory exists, creates it if it does not
     *
     * @param  string $dir
     * @return bool
     */
    public function ensuredir($dir);
}
