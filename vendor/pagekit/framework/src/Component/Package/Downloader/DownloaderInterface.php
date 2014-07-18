<?php

namespace Pagekit\Component\Package\Downloader;

use Pagekit\Component\Package\Exception\DownloadErrorException;
use Pagekit\Component\Package\PackageInterface;

interface DownloaderInterface
{
    /**
     * Downloads specific package into specific folder.
     *
     * @param  PackageInterface $package
     * @param  string           $path
     * @throws DownloadErrorException
     */
    public function download(PackageInterface $package, $path);
}
