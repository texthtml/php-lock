<?php

namespace TH\Lock;

interface Factory
{
    /**
     * Create a Lock for $resource
     *
     * @param string $resource  resource identifier
     * @return Lock
     */
    public function create($resource);
}
