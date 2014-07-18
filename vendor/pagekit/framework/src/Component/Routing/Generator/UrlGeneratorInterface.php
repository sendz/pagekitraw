<?php

namespace Pagekit\Component\Routing\Generator;

interface UrlGeneratorInterface
{
    /**
     * Generates a link url.
     */
    const LINK_URL = 'link';

    /**
     * Generates a path relative to the executed script, e.g. "/dir/file".
     */
    const BASE_PATH = 'base';
}
