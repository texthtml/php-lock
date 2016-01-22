<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;

interface Lock
{
    /**
     * Acquire a lock on the resource
     * @throws \RuntimeException thrown if the lock could not be acquired
     * @return void
     */
    public function acquire();

    /**
     * Release lock on the resource. If the lock has not been acquired, does nothing.
     * @throws \RuntimeException thrown if the lock could not be released
     * @return void
     */
    public function release();
}
