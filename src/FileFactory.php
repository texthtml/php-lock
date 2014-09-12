<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FileFactory implements LockFactory
{
    private $lock_dir;
    private $logger;
    private $hash_algo;

    public function __construct($lock_dir, $hash_algo = 'sha256')
    {
        $this->logger    = new NullLogger;
        $this->lock_dir  = $lock_dir;
        $this->hash_algo = $hash_algo;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create($resource, $owner = null)
    {
        if (!is_dir($this->lock_dir)) {
            mkdir($this->lock_dir, 0777, true);
        }

        $path = $this->lock_dir.'/'.hash($this->hash_algo, serialize($resource)).'.lock';

        $lock = new FileLock($path, $resource, $owner);

        $lock->setLogger($this->logger);

        return $lock;
    }
}
