<?php

namespace Pagekit\Component\Database\ORM\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Entity implements Annotation
{
    /** @var string */
    public $repositoryClass;

    /** @var string */
    public $tableClass;

    /** @var string */
    public $eventPrefix;
}
