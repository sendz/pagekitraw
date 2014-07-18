<?php

namespace Pagekit\Component\File;

use Pagekit\Component\File\Adapter\AdapterInterface;
use Pagekit\Component\File\Adapter\FilesystemAdapter;

class Filesystem implements FilesystemInterface
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    public function __construct(AdapterInterface $adapter = null)
    {
        $this->adapter = $adapter ?: new FilesystemAdapter;
    }

    /**
     * {@inheritdoc}
     */
	public function getSize($file)
	{
        return $this->adapter->size($file);
	}

    /**
     * {@inheritdoc}
     */
	public function getModified($file)
	{
        return $this->adapter->mtime($file);
	}

    /**
     * {@inheritdoc}
     */
    public function getContents($file)
    {
        return $this->adapter->read($file);
    }

    /**
     * {@inheritdoc}
     */
	public function putContents($file, $data, $append = false)
	{
        return $this->adapter->write($file, $data, $append);
	}

    /**
     * {@inheritdoc}
     */
    public function delete($files)
    {
        foreach ($this->toIterator($files) as $file) {
            if ($this->adapter->isdir($file)) {

                $result = $this->adapter->lsdir($file);

                foreach (array_merge($result['dirs'], $result['files']) as $res) {
                    if (!$this->delete($file.'/'.$res)) {
                        return false;
                    }
                }

                if (!$this->adapter->rmdir($file)) {
                    return false;
                }

            } elseif (!$this->adapter->unlink($file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($originFile, $targetFile)
	{
        if (!$this->makeDir(dirname($targetFile), 0777, true)) {
            return false;
        }

        return $this->adapter->copy($originFile, $targetFile);
	}

    /**
     * {@inheritdoc}
     */
    public function copyDir($originDir, $targetDir)
    {
        if (!$this->makeDir($targetDir, 0777, true)) {
            return false;
        }

        $result = $this->adapter->lsdir($originDir);

        $originDir = $originDir ? $originDir.'/' : '';
        $targetDir = $targetDir ? $targetDir.'/' : '';

        foreach ($result['files'] as $file) {
            if (!$this->copy($originDir.$file, $targetDir.$file)) {
                return false;
            }
        }

        foreach($result['dirs'] as $dir) {
            if (!$this->copyDir($originDir.$dir, $targetDir.$dir)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rename($origin, $target)
    {
        return $this->adapter->rename($origin, $target);
    }

    /**
     * {@inheritdoc}
     */
    public function makeDir($dirs, $mode = 0777)
    {
        foreach ($this->toIterator($dirs) as $dir) {
            if ($this->adapter->isdir($dir)) {
                continue;
            }
            if (!$this->adapter->mkdir($dir, $mode, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function ensureDirExists($dirs)
    {
        foreach ($this->toIterator($dirs) as $dir) {
            if (!$this->adapter->ensuredir($dir)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($files)
    {
        foreach ($this->toIterator($files) as $file) {
            if (!$this->adapter->exists($file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function changeMode($files, $mode, $umask = 0000, $recursive = false)
    {
        foreach ($this->toIterator($files) as $file) {
            if ($recursive && $this->adapter->isdir($file)) {

                $result = $this->adapter->lsdir($file);

                foreach (array_merge($result['dirs'], $result['files']) as $res) {
                    if (!$this->changeMode($file.'/'.$res, $mode, $umask, $recursive)) {
                        return false;
                    }
                }

            } elseif (!$this->adapter->chmod($file, $mode, $umask)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function changeOwner($files, $user, $recursive = false)
    {
        foreach ($this->toIterator($files) as $file) {
            if ($recursive && $this->adapter->isdir($file)) {

                $result = $this->adapter->lsdir($file);

                foreach (array_merge($result['dirs'], $result['files']) as $res) {
                    if (!$this->changeOwner($file.'/'.$res, $user, $recursive)) {
                        return false;
                    }
                }

            } elseif (!$this->adapter->chown($file, $user)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function changeGroup($files, $group, $recursive = false)
    {
        foreach ($this->toIterator($files) as $file) {
            if ($recursive && $this->adapter->isdir($file)) {

                $result = $this->adapter->lsdir($file);

                foreach (array_merge($result['dirs'], $result['files']) as $res) {
                    if (!$this->changeGroup($file.'/'.$res, $group, $recursive)) {
                        return false;
                    }
                }

            } elseif (!$this->adapter->chgrp($file, $group)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $files
     *
     * @return \Traversable
     */
    protected function toIterator($files)
    {
        if (!$files instanceof \Traversable) {
            $files = new \ArrayObject(is_array($files) ? $files : [$files]);
        }

        return $files;
    }
}
