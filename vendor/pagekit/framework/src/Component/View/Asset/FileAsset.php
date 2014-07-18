<?php

namespace Pagekit\Component\View\Asset;

class FileAsset extends Asset
{
	/**
	 * @var string
	 */
	protected $content;

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if ($this->content === null && $this['path']) {
            $this->content = file_get_contents($this['path']);
        }

        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}
