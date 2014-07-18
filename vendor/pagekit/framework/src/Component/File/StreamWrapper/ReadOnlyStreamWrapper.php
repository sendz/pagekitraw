<?php

namespace Pagekit\Component\File\StreamWrapper;

use Pagekit\Component\File\Exception\BadMethodCallException;

class ReadOnlyStreamWrapper extends StreamWrapper
{
    /**
     * {@inheritdoc}
     */
    public function stream_open($uri, $mode, $options, &$opened_url)
    {
        if (!in_array($mode, ['r', 'rb', 'rt'])) {
            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('stream_open() write modes not supported for read-only stream wrappers', E_USER_WARNING);
            }
            return false;
        }

        return parent::stream_open($uri, $mode, $options, $opened_url);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_lock($operation)
    {
        if (in_array($operation, [LOCK_EX, LOCK_EX|LOCK_NB])) {
            trigger_error('stream_lock() exclusive lock operations not supported for read-only stream wrappers', E_USER_WARNING);
        }

        return parent::stream_lock($operation);
    }

    /**
     * {@inheritdoc}
     * @throws BadMethodCallException
     */
    public function stream_write($data)
    {
        throw new BadMethodCallException('ReadOnlyStreamWrapper does not support "stream_write" function');
    }

    /**
     * {@inheritdoc}
     */
    public function stream_flush()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     * @throws BadMethodCallException
     */
    public function unlink($uri)
    {
        throw new BadMethodCallException('ReadOnlyStreamWrapper does not support "unlink" function');
    }

    /**
     * @param  string $from_uri
     * @param  string $to_uri
     * @throws BadMethodCallException
     */
    public function rename($from_uri, $to_uri)
    {
        throw new BadMethodCallException('ReadOnlyStreamWrapper does not support "rename" function');
    }

    /**
     * {@inheritdoc}
     * @throws BadMethodCallException
     */
    public function mkdir($uri, $mode, $options)
    {
        throw new BadMethodCallException('ReadOnlyStreamWrapper does not support "mkdir" function');
    }

    /**
     * {@inheritdoc}
     * @throws BadMethodCallException
     */
    public function rmdir($uri, $options)
    {
        throw new BadMethodCallException('ReadOnlyStreamWrapper does not support "rmdir" function');
    }
}
