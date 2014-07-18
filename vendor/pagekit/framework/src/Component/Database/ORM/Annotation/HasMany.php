<?php

namespace Pagekit\Component\Database\ORM\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class HasMany implements Annotation
{
    /** @var string */
    public $targetEntity;

    /** @var string */
    public $keyFrom;

    /** @var string */
    public $keyTo;
}
