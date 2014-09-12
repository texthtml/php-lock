<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;

interface Lock
{
    public function acquire();
    public function release();
    public function setLogger(LoggerInterface $logger);
}
