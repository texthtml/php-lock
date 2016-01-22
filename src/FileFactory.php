<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FileFactory implements Factory
{
    private $lock_dir;
    private $hash_algo;

    private $logger;

    /**
     * Create a new FileFactory
     * @param string               $lock_dir  directory where lock files will be created
     * @param string               $hash_algo hashing algotithms used to generate lock file name from identifier
     *                                        see http://php.net/manual/en/function.hash-algos.php
     * @param LoggerInterface|null $logger
     */
    public function __construct($lock_dir, $hash_algo = 'sha256', LoggerInterface $logger = null)
    {
        $this->lock_dir  = $lock_dir;
        $this->hash_algo = $hash_algo;

        $this->logger = $logger ?: new NullLogger;
    }

    /**
     * Create a FileLock for $resource
     * @param string      $resource  resource identifier
     * @param boolean     $exclusive true for an exclusive lock, false for shared one
     * @param boolean     $blocking  true to wait for lock to be available, false to throw exception instead of waiting
     * @return FileLock
     */
    public function create(
        $resource,
        $exclusive = FileLock::EXCLUSIVE,
        $blocking = FileLock::NON_BLOCKING
    ) {
        if (!is_dir($this->lock_dir)) {
            mkdir($this->lock_dir, 0777, true);
        }
        $hash = hash($this->hash_algo, serialize($resource));
        $path = "{$this->lock_dir}/$hash.lock";
        return new FileLock($path, $exclusive, $blocking, true, $this->logger);
    }
}
