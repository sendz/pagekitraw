<?php

namespace Pagekit\Component\Package\Downloader;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TransferException;
use Pagekit\Component\File\Archive\Zip;
use Pagekit\Component\File\Filesystem;
use Pagekit\Component\File\FilesystemInterface;
use Pagekit\Component\Package\Exception\ArchiveExtractionException;
use Pagekit\Component\Package\Exception\ChecksumVerificationException;
use Pagekit\Component\Package\Exception\DownloadErrorException;
use Pagekit\Component\Package\Exception\NotWritableException;
use Pagekit\Component\Package\Exception\UnauthorizedDownloadException;
use Pagekit\Component\Package\Exception\UnexpectedValueException;
use Pagekit\Component\Package\PackageInterface;

class PackageDownloader implements DownloaderInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var FilesystemInterface
     */
    protected $files;

    /**
     * Constructor.
     *
     * @param FilesystemInterface $files
     * @param ClientInterface     $client
     */
    public function __construct(ClientInterface $client = null, FilesystemInterface $files = null)
    {
        $this->client = $client ?: new Client;
        $this->files  = $files  ?: new Filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function download(PackageInterface $package, $path)
    {
        if (!$url = $package->getDistUrl()) {
            throw new UnexpectedValueException("The given package is missing url information");
        }

        $this->downloadFile($path, $url, $package->getDistSha1Checksum());
    }

    /**
     * Downlod a package file.
     *
     * @param string $path
     * @param string $url
     * @param string $shasum
     */
    public function downloadFile($path, $url, $shasum = '')
    {
        $file = $path.'/'.uniqid();

        try {

            $data = $this->client->get($url)->getBody();

            if ($shasum && sha1($data) !== $shasum) {
                throw new ChecksumVerificationException("The file checksum verification failed");
            }

            if (!$this->files->makeDir($path) || !$this->files->putContents($file, $data)) {
                throw new NotWritableException("The path is not writable ($path)");
            }

            if (Zip::extract($file, $path) !== true) {
                throw new ArchiveExtractionException("The file extraction failed");
            }

            $this->files->delete($file);

        } catch (\Exception $e) {

            $this->files->delete($path);

            if ($e instanceof TransferException) {

                if ($e instanceof BadResponseException) {
                    throw new UnauthorizedDownloadException("Unauthorized download ($url)");
                }

                throw new DownloadErrorException("The file download failed ($url)");
            }

            throw $e;
        }
    }
}
