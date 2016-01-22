<?php

namespace TH\Lock;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LockSet implements Lock
{
    private $locks = [];

    private $logger;

    /**
     * @param Lock[]               $locks  array of Lock
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        array $locks,
        LoggerInterface $logger = null
    ) {
        if (empty($locks)) {
            throw new RuntimeException("Lock set cannot be empty");
        }
        $this->locks = $locks;
        $this->logger = $logger ?: new NullLogger;
    }

    /**
     * @inherit
     */
    public function acquire()
    {
        $acquiredLocks = [];
        try {
            foreach ($this->locks as $lock) {
                $lock->acquire();
                $acquiredLocks[] = $lock;
            }
        } catch (RuntimeException $e) {
            foreach ($acquiredLocks as $lock) {
                $lock->release();
            }
            throw $e;
        }
    }

    public function release()
    {
        $exceptions = [];
        foreach ($this->locks as $lock) {
            try {
                $lock->release();
            } catch (RuntimeException $e) {
                $exceptions[] = $e;
            }
        }
        if (!empty($exceptions)) {
            throw new AggregationException($exceptions, "Some locks were not released");
        }
    }

    public function __destruct()
    {
        $this->release();
    }
}
