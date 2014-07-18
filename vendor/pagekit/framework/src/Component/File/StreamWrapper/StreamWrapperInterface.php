<?php

namespace Pagekit\Component\File\StreamWrapper;

interface StreamWrapperInterface
{
    /**
     * @return bool
     */
    public function dir_closedir();

    /**
     * @param  string $uri
     * @param  int $options
     * @return bool
     */
    public function dir_opendir($uri, $options);

    /**
     * @return string
     */
    public function dir_readdir();

    /**
     * @return bool
     */
    public function dir_rewinddir();

    /**
     * @param  string $uri
     * @param  int    $mode
     * @param  int    $options
     * @return bool
     */
    public function mkdir($uri, $mode, $options);

    /**
     * @param  string $olduri
     * @param  string $newuri
     * @return bool
     */
    public function rename($olduri, $newuri);

    /**
     * @param  string $uri
     * @param  int    $options
     * @return bool
     */
    public function rmdir($uri, $options);

    public function stream_close();

    /**
     * @return bool
     */
    public function stream_eof();

    /**
     * @return bool
     */
    public function stream_flush();

    /**
     * @param  int $operation
     * @return bool
     */
    public function stream_lock($operation);

    /**
     * @param  string $uri
     * @param  string $mode
     * @param  int    $options
     * @param  string $opened_url
     * @return bool
     */
    public function stream_open($uri, $mode, $options, &$opened_url);

    /**
     * @param  int $count
     * @return string
     */
    public function stream_read($count);

    /**
     * @param  int $offset
     * @param  int $whence
     * @return bool
     */
    public function stream_seek($offset, $whence);


    /**
     * @return int
     */
    public function stream_tell();

    /**
     * @param  string $data
     * @return int
     */
    public function stream_write($data);

    /**
     * @return array
     */
    public function stream_stat();

    /**
     * @param  string $uri
     * @return bool
     */
    public function unlink($uri);

    /**
     * @param  string $uri
     * @param  int    $flags
     * @return array
     */
    public function url_stat($uri, $flags);
}