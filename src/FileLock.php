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
    private $identifier;
    private $owner;
    private $fh;

    public function __construct($lock_file, $identifier = null, $owner = null)
    {
        $this->lock_file  = $lock_file;
        $this->identifier = $identifier?:$lock_file;
        $this->owner      = $owner === null ? '' : $owner.': ';

        $this->logger = new NullLogger;
    }

    public function acquire($exclusive = FileLock::EXCLUSIVE, $blocking = FileLock::NON_BLOCKING)
    {
        $operation  = ($exclusive ? LOCK_EX : LOCK_SH) | ($blocking ? 0 : LOCK_NB);

        if (!flock($this->fh(), $operation)) {
            $this->logger->debug(($exclusive ?
                '{owner} could not acquire exclusive lock on {identifier}':
                '{owner} could not acquire shared lock on {identifier}'
            ), [
                'identifier' => $this->identifier,
                'owner' => $this->owner
            ]);

            throw new Exception(($exclusive ?
                'Could not acquire exclusive lock on '.$this->identifier:
                'Could not acquire shared lock on '.$this->identifier
            ));

        }

        $this->logger->debug(($exclusive ?
            '{owner} exclusive lock acquired on {identifier}':
            '{owner} shared lock acquired on {identifier}'
        ), [
            'identifier' => $this->identifier,
            'owner' => $this->owner
        ]);
    }

    public function release()
    {
        if ($this->fh === null) {
            return;
        }

        if(flock($this->fh, LOCK_EX)) {
            fclose($this->fh);
            $this->fh = null;
            unlink($this->lock_file);
        } else {
            flock($this->fh, LOCK_UN);
        }


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
