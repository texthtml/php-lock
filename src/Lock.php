<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;

interface Lock
{
    /**
     * Acquire a lock on the resource
     * @return void
     */
    public function acquire();

    /**
     * Release lock on the resource
     * @return void
     */
    public function release();
}
