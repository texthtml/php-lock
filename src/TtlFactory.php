<?php

namespace TH\Lock;

interface TtlFactory extends Factory
{
    /**
     * Create a Lock for $resource
     *
     * @param string $resource  resource identifier
     * @param integer $ttl      lock time-to-live in milliseconds
     * @return Lock
     */
    public function create($resource, $ttl = null);
}
