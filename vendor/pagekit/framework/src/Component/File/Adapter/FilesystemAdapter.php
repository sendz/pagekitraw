<?php

namespace Pagekit\Component\File\Adapter;

class FilesystemAdapter implements AdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function chmod($file, $mode, $umask = 0000)
    {
        return chmod($this->normalizePath($file), $mode & ~$umask);
    }

    /**
     * {@inheritdoc}
     */
    public function chown($file, $user)
    {
        $file = $this->normalizePath($file);

        return is_link($file) && function_exists('lchown') ? lchown($file, $user) : chown($file, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function chgrp($file, $group)
    {
        $file = $this->normalizePath($file);

        return is_link($file) && function_exists('lchgrp') ? lchgrp($file, $group) : chgrp($file, $group);
    }

    /**
     * {@inheritdoc}
     */
    public function read($file)
    {
        return @file_get_contents($this->normalizePath($file));
    }

    /**
     * {@inheritdoc}
     */
    public function write($file, $data, $append)
    {
        $path = $this->normalizePath($file);

        $flags = LOCK_EX;

        if (strpos($path, '://')) {
            $flags = 0;
        }

        return file_put_contents($this->normalizePath($file), $data, $append ? $flags | FILE_APPEND : $flags);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($file)
    {
        return file_exists($this->normalizePath($file));
    }

    /**
     * {@inheritdoc}
     */
    public function rmdir($dir)
    {
        return @rmdir($this->normalizePath($dir));
    }

    /**
     * {@inheritdoc}
     */
    public function unlink($file)
    {
        return @unlink($this->normalizePath($file));
    }

    /**
     * {@inheritdoc}
     */
    public function rename($oldname, $newname)
    {
        return rename($this->normalizePath($oldname), $this->normalizePath($newname));
    }

    /**
     * {@inheritdoc}
     */
    public function copy($source, $dest)
    {
        return copy($this->normalizePath($source), $this->normalizePath($dest));
    }

    /**
     * {@inheritdoc}
     */
	public function mtime($file)
    {
        return @filemtime($this->normalizePath($file));
    }

    /**
     * @{inheritdoc}
     */
	public function size($file)
    {
        return @filesize($this->normalizePath($file));
    }

    /**
     * {@inheritdoc}
     */
    public function isdir($dir)
    {
        return is_dir($this->normalizePath($dir));
    }

    /**
     * @{inheritdoc}
     */
    public function lsdir($dir = '')
    {
        $dir = $this->normalizePath($dir);

        $files = $dirs = [];

        if (is_dir($dir)) {
            foreach (new \DirectoryIterator($dir) as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $files[] = $fileinfo->getFilename();
                } elseif ($fileinfo->isDir() && !$fileinfo->isDot()) {
                    $dirs[] = $fileinfo->getFilename();
                }
            }
        }

        return [
           'files'   => $files,
           'dirs'   => $dirs
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir($dir, $mode = 0777, $recursive = false)
    {
        return mkdir($this->normalizePath($dir), $mode, $recursive);
    }

    /**
     * @{inheritdoc}
     */
    public function ensuredir($dir)
    {
        return !$this->isDir($dir) ? $this->mkdir($dir, 0777, true) : true;
    }

    /**
     * Normalizes the given path
     *
     * @param  string $path
     * @return string
     */
    public function normalizePath($path)
    {
        $path   = str_replace(['\\', '//'], '/', $path);
        $prefix = preg_match('|^(?P<prefix>([a-zA-Z]+:)?//?)|', $path, $matches) ? $matches['prefix'] : '';
        $path   = substr($path, strlen($prefix));
        $parts  = array_filter(explode('/', $path), 'strlen');
        $tokens = [];

        foreach ($parts as $part) {
            if ('..' === $part) {
                array_pop($tokens);
            } elseif ('.' !== $part) {
                array_push($tokens, $part);
            }
        }

        return $prefix . implode('/', $tokens);
    }
}
