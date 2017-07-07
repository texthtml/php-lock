<?php

namespace spec\TH\Lock;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use TH\Lock\FileLock;
use TH\Lock\Lock;
use VirtualFileSystem\FileSystem;

class FileLockSpec extends ObjectBehavior
{
    private $fs;
    private $lock_file;

    public function let()
    {
        $this->fs = new FileSystem;
        $this->lock_file = $this->fs->path("/path/to/lock_file.lock");

        mkdir(dirname($this->lock_file), 0777, true);

        $this->beConstructedWith($this->lock_file);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FileLock::class);
        $this->shouldImplement(Lock::class);
    }

    public function it_should_acquire_an_exclusive_lock()
    {
        $this->acquire();

        if (!file_exists($this->lock_file)) {
            throw new \Exception("Lock file was not created");
        }

        if (flock(fopen($this->lock_file, "r"), LOCK_SH|LOCK_NB)) {
            throw new \Exception("Lock file was not exclusively locked");
        }
    }

    public function it_should_acquire_a_shared_lock()
    {
        $this->beConstructedWith($this->lock_file, FileLock::SHARED);

        $this->acquire();

        if (!file_exists($this->lock_file)) {
            throw new \Exception("Lock file was not created");
        }

        if (flock(fopen($this->lock_file, "r"), LOCK_EX|LOCK_NB)) {
            throw new \Exception("Lock file was not locked againt exclusive lock");
        }

        if (!flock(fopen($this->lock_file, "r"), LOCK_SH|LOCK_NB)) {
            throw new \Exception("Lock file was not shared locked");
        }
    }

    public function it_should_release_a_lock()
    {
        $this->acquire();
        $this->release();

        if (!flock(fopen($this->lock_file, "r"), LOCK_EX|LOCK_NB)) {
            throw new \Exception("Lock file was not released");
        }
    }

    public function it_should_throw_if_it_cannot_lock()
    {
        touch($this->lock_file);
        flock(fopen($this->lock_file, "r"), LOCK_SH|LOCK_NB);

        $this->shouldThrow()->duringAcquire();
    }

    public function it_remove_its_lock_file_if_not_locked()
    {
        $this->beConstructedWith($this->lock_file, FileLock::EXCLUSIVE, FileLock::NON_BLOCKING, true);

        $this->acquire();
        $this->release();

        if (file_exists($this->lock_file)) {
            throw new \Exception("Lock file was not removed");
        }
    }

    public function it_does_not_remove_its_lock_file_if_still_locked()
    {
        $this->beConstructedWith($this->lock_file, FileLock::SHARED, FileLock::NON_BLOCKING, true);

        touch($this->lock_file);
        flock(fopen($this->lock_file, "r"), LOCK_SH|LOCK_NB);

        $this->acquire();
        $this->release();

        if (!file_exists($this->lock_file)) {
            throw new \Exception("Lock file was removed");
        }
    }

    public function it_can_acquire_then_release_and_acquire_again()
    {
        $this->beConstructedWith($this->lock_file, FileLock::EXCLUSIVE, FileLock::NON_BLOCKING, true);

        $this->acquire();
        $this->release();
        $this->acquire();

        if (!file_exists($this->lock_file)) {
            throw new \Exception("Lock file was not created");
        }

        if (flock(fopen($this->lock_file, "r"), LOCK_SH|LOCK_NB)) {
            throw new \Exception("Lock file was not exclusively locked");
        }

        $this->release();
    }

    public function it_does_not_throw_when_lock_file_does_not_exists()
    {
        $this->acquire();

        unlink($this->lock_file);

        $this->release();
    }
}
