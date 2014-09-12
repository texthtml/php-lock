<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Exception;

class FileLock implements Lock
{
    private $identifier;
    private $lock_file;
    private $owner;
    private $operation;
    private $fh;

    public function __construct($lock_file, $identifier = null, $owner = null, $operation = LOCK_EX, $blocking = false)
    {
        $this->identifier = $identifier?:$lock_file;
        $this->lock_file     = $lock_file;
        $this->owner         = $owner === null ? '' : $owner.': ';
        $this->operation     = $operation | LOCK_NB;

        if ($blocking) {
            $this->operation &= ~LOCK_NB;
        }

        $this->logger = new NullLogger;
    }

    public function acquire()
    {
        $blocking  = !$this->operation & LOCK_NB;
        $exclusive = $this->operation & LOCK_EX;

        if (!flock($this->fh(), $this->operation)) {
            $this->logger->debug(($exclusive ?
                '{owner} could not acquire exclusive lock on {identifier}':
                '{owner} could not acquire shared lock on {identifier}'
            ), [
                'identifier' => $this->identifier,
                'owner' => $this->owner
            ]);

            throw new Exception(
                'Could not acquire exclusive lock on '.($this->identifier ? $this->identifier : $this->lock_file)
            );

        }

        $this->logger->debug(($exclusive ?
            '{owner} exclusive lock acquired on {identifier}':
            '{owner} shared lock acquired on {identifier}'
        ), [
            'identifier' => $this->identifier,
            'owner' => $this->owner
        ]);

        return true;
    }

    public function release()
    {
        if ($this->fh === null) {
            return;
        }

        flock($this->fh, LOCK_UN);

        $this->logger->debug(
            '{owner} lock released on {identifier}',
            [
                'identifier' => $this->identifier,
                'owner' => $this->owner
            ]
        );
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __destruct()
    {
        $this->release();
    }

    private function fh()
    {
        if ($this->fh === null) {
            $this->fh = fopen($this->lock_file, 'c');
        }

        return $this->fh;
    }
}
