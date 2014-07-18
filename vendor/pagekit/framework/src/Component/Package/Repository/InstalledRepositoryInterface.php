<?php

namespace Pagekit\Component\Package\Repository;

use Pagekit\Component\Package\PackageInterface;

interface InstalledRepositoryInterface extends RepositoryInterface
{
    /**
     * Checks if specified package registered.
     *
     * @param  PackageInterface $package
     * @return string
     */
    public function getInstallPath(PackageInterface $package);
}
