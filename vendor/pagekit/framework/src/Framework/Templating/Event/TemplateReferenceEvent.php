<?php

namespace Pagekit\Framework\Templating\Event;

use Pagekit\Framework\Event\Event;
use Symfony\Component\Templating\TemplateReferenceInterface;

class TemplateReferenceEvent extends Event
{
    /**
     * @var TemplateReferenceInterface
     */
    protected $template;

    /**
     * Constructor.
     *
     * @param TemplateReferenceInterface $template
     */
    public function __construct(TemplateReferenceInterface $template)
    {
        $this->template = $template;
    }

    /**
     * @return TemplateReferenceInterface
     */
    public function getTemplateReference()
    {
        return $this->template;
    }
}