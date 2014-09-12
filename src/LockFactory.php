<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;

interface LockFactory
{
    public function create($resource, $owner = null);
    public function setLogger(LoggerInterface $logger);
}
