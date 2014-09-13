<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;

interface LockFactory
{
    /**
     * Create a lock handle on $resource
     * @param  string      $resource resource identifier
     * @param  string|null $owner    owner name (for logging)
     * @return Lock
     */
    public function create($resource, $owner = null);

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger);
}
