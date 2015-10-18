<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FileFactory implements LockFactory
{
    private $lock_dir;
    private $hash_algo;

    private $logger;

    public function __construct($lock_dir, $hash_algo = 'sha256', LoggerInterface $logger = null)
    {
        $this->lock_dir  = $lock_dir;
        $this->hash_algo = $hash_algo;

        $this->logger = $logger ?: new NullLogger;
    }

    public function create($resource, $owner = null)
    {
        if (!is_dir($this->lock_dir)) {
            mkdir($this->lock_dir, 0777, true);
        }

        $path = $this->lock_dir.'/'.hash($this->hash_algo, serialize($resource)).'.lock';

        $lock = new FileLock($path, $resource, $owner, true, $this->logger);

        return $lock;
    }
}
