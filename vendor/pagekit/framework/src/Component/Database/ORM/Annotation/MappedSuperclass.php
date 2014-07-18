<?php

namespace Pagekit\Component\Database\ORM\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class MappedSuperclass implements Annotation
{
    /** @var string */
    public $repositoryClass;
}