<?php

namespace Pagekit\Component\Filter;

interface FilterInterface
{
    /**
     * Returns the filtered value.
     *
     * @param  mixed $value
     * @return mixed
     */
    public function filter($value);
}