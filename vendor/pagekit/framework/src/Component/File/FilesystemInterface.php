<?php

namespace Pagekit\Component\File;

interface FilesystemInterface
{
    /**
     * Get the file size of a given file.
     *
     * @param  string $file
     * @return int|false
     */
    public function getSize($file);

    /**
     * Get a file's or directory's modified timestamp.
     *
     * @param  string $file
     * @return int|false
     */
    public function getModified($file);

    /**
     * Read the contents of the file
     *
     * @param  string $file
     * @return string|false
     */
    public function getContents($file);

    /**
     * Write to a file.
     *
     * @param  string  $file
     * @param  string  $data
     * @param  bool $append
     * @return int|false
     */
    public function putContents($file, $data, $append = false);

    /**
     * Removes files or directories.
     *
     * @param  string|array|\Traversable $files
     * @return bool
     */
    public function delete($files);

    /**
     * Copies a file.
     *
     * @param  string $originFile
     * @param  string $targetFile
     * @return bool
     */
    public function copy($originFile, $targetFile);

    /**
     * Mirrors a directory to another.
     *
     * @param  string $originDir
     * @param  string $targetDir
     * @return bool
     */
    public function copyDir($originDir, $targetDir);

    /**
     * Renames a file.
     *
     * @param  string $origin
     * @param  string $target
     * @return bool
     */
    public function rename($origin, $target);

    /**
     * Creates a directory recursively.
     *
     * @param  string|array|\Traversable $dirs
     * @param  int                       $mode
     * @return bool
     */
    public function makeDir($dirs, $mode = 0777);

    /**
     * Ensures the specified directory exists, creates it if it does not
     *
     * @param  string|array|\Traversable $dirs
     * @return bool
     */
    public function ensureDirExists($dirs);

    /**
     * Checks the existence of files or directories.
     *
     * @param string|array|\Traversable $files
     * @return bool
     */
    public function exists($files);

    /**
     * Change mode for an array of files or directories.
     *
     * @param  string|array|\Traversable $files
     * @param  int                       $mode
     * @param  int                       $umask
     * @param  bool                      $recursive
     * @return bool
     */
    public function changeMode($files, $mode, $umask = 0000, $recursive = false);

    /**
     * Change the owner of an array of files or directories
     *
     * @param  string|array|\Traversable $files
     * @param  string                    $user
     * @param  bool                      $recursive
     * @return bool
     */
    public function changeOwner($files, $user, $recursive = false);

    /**
     * Change the group of an array of files or directories
     *
     * @param  string|array|\Traversable $files
     * @param  string                    $group
     * @param  bool                      $recursive
     * @return bool
     */
    public function changeGroup($files, $group, $recursive = false);
}
