<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Exception;

class FileLock implements Lock
{
    const EXCLUSIVE = true;
    const SHARED = false;

    const BLOCKING = true;
    const NON_BLOCKING = false;

    private $lock_file;
    private $exclusive;
    private $blocking;
    private $identifier;
    private $owner;
    private $fh;
    private $remove_on_release;

    private $logger;

    /**
     * @param string          $lock_file         path to file
     * @param boolean         $exclusive         true for an exclusive lock, false for shared one
     * @param boolean         $blocking          true to wait for lock to be available,
     *                                           false to throw exception instead of waiting
     * @param string|null     $identifier        resource identifier (default to $lock_file) for logging
     * @param string|null     $owner             owner name for logging
     * @param boolean         $remove_on_release remove file on release if no other lock remains
     * @param LoggerInterface $logger
     */
    public function __construct(
        $lock_file,
        $exclusive = FileLock::EXCLUSIVE,
        $blocking = FileLock::NON_BLOCKING,
        $identifier = null,
        $owner = null,
        $remove_on_release = false,
        LoggerInterface $logger = null
    ) {
        $this->lock_file         = $lock_file;
        $this->exclusive         = $exclusive;
        $this->blocking          = $blocking;
        $this->identifier        = $identifier?:$lock_file;
        $this->owner             = $owner === null ? '' : $owner.': ';
        $this->remove_on_release = $remove_on_release;

        $this->logger = $logger ?: new NullLogger;
    }

    /**
     * @inherit
     */
    public function acquire()
    {
        if ($this->exclusive === FileLock::EXCLUSIVE) {
            $lock_type = 'exclusive';
            $operation = LOCK_EX;
        } else {
            $lock_type = 'shared';
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
            'identifier' => $this->identifier,
            'owner' => $this->owner,
            'lock_type' => $lock_type
        ];

        if (!$this->flock($operation)) {
            $this->logger->debug('{owner} could not acquire {lock_type} lock on {identifier}', $log_data);

            throw new Exception(
                'Could not acquire '.$lock_type.' lock on '.$this->identifier
            );

        }

        $this->logger->debug('{owner} {lock_type} lock acquired on {identifier}', $log_data);
    }

    public function release()
    {
        if ($this->fh === null) {
            return;
        }

        if ($this->remove_on_release && $this->flock(LOCK_EX | LOCK_NB)) {
            unlink($this->lock_file);
        }

        $this->flock(LOCK_UN);
        fclose($this->fh);
        $this->fh = null;

        $this->logger->debug('{owner} lock released on {identifier}', [
            'identifier' => $this->identifier,
            'owner' => $this->owner
        ]);
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
            $this->fh = fopen($this->lock_file, 'c');
        }

        if (!is_resource($this->fh)) {
            throw new Exception('Could not open lock file '.$this->lock_file);
        }

        return flock($this->fh, $operation);
    }
}
