<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FileLock implements Lock
{
    const EXCLUSIVE = true;
    const SHARED = false;

    const BLOCKING = true;
    const NON_BLOCKING = false;

    private $lock_file;
    private $exclusive;
    private $blocking;
    private $fh;
    private $remove_on_release;

    private $logger;

    /**
     * @param string          $lock_file         path to file
     * @param boolean         $exclusive         true for an exclusive lock, false for shared one
     * @param boolean         $blocking          true to wait for lock to be available,
     *                                           false to throw exception instead of waiting
     * @param boolean         $remove_on_release remove file on release if no other lock remains
     * @param LoggerInterface $logger
     */
    public function __construct(
        $lock_file,
        $exclusive = FileLock::EXCLUSIVE,
        $blocking = FileLock::NON_BLOCKING,
        $remove_on_release = false,
        LoggerInterface $logger = null
    ) {
        $this->lock_file         = $lock_file;
        $this->exclusive         = $exclusive;
        $this->blocking          = $blocking;
        $this->remove_on_release = $remove_on_release;

        $this->logger = $logger ?: new NullLogger;
    }

    /**
     * @inherit
     */
    public function acquire()
    {
        if ($this->exclusive === FileLock::EXCLUSIVE) {
            $lock_type = "exclusive";
            $operation = LOCK_EX;
        } else {
            $lock_type = "shared";
            $operation = LOCK_SH;
        }

        if ($this->blocking === FileLock::NON_BLOCKING) {
            $operation |= LOCK_NB;
        }

        $this->tryAcquire($operation, $lock_type);
    }

    /**
     * try to acquire lock on file, throw in case of faillure
     * @param  int    $operation
     * @param  string $lock_type lock type description
     * @return void
     * @see https://php.net/flock
     */
    private function tryAcquire($operation, $lock_type)
    {
        $log_data = [
            "lock_file" => $this->lock_file,
            "lock_type" => $lock_type
        ];

        if (!$this->flock($operation)) {
            $this->logger->debug("could not acquire {lock_type} lock on {lock_file}", $log_data);

            throw new RuntimeException(
                "Could not acquire $lock_type lock on {$this->lock_file}"
            );

        }

        $this->logger->debug("{lock_type} lock acquired on {lock_file}", $log_data);
    }

    public function release()
    {
        if ($this->fh === null) {
            return;
        }

        if ($this->remove_on_release && $this->flock(LOCK_EX | LOCK_NB)) {
            if (is_file($this->lock_file)) {
                unlink($this->lock_file);
            }
        }

        $this->flock(LOCK_UN);
        fclose($this->fh);
        $this->fh = null;

        $this->logger->debug("{lock_type} lock released on {lock_file}", ["lock_file" => $this->lock_file]);
    }

    public function __destruct()
    {
        $this->release();
    }

    /**
     * @return boolean
     */
    private function flock($operation)
    {
        if ($this->fh === null) {
            $this->fh = fopen($this->lock_file, "c");
        }

        if (!is_resource($this->fh)) {
            throw new RuntimeException("Could not open lock file {$this->lock_file}");
        }

        return flock($this->fh, $operation);
    }
}
