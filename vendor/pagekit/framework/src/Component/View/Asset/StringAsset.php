<?php

namespace Pagekit\Component\View\Asset;

class StringAsset extends Asset
{
    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
		return $this->asset;
	}

    /**
     * {@inheritdoc}
     */
    public function setContent($content)
    {
        $this->asset = $content;
    }
}
