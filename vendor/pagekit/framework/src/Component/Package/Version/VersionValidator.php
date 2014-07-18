<?php

namespace Pagekit\Component\Package\Version;

class VersionValidator
{
    /**
     * Validates a version
     *
     * @param  string $version
     * @return bool
     */
    public static function validate($version)
    {
        return preg_match('/^\d+\.\d+\.\d+(-(pre|beta|b|RC|alpha|a|pl|p)([\.]?(\d+))?)?$/', $version);
    }
}
