<?php

namespace spec\TH\Lock;

use VirtualFileSystem\FileSystem;
use TH\Lock\FileLock;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Exception;

class FileLockSpec extends ObjectBehavior
{
    private $fs;
    private $lock_file;

    public function let()
    {
        $this->fs = new FileSystem;
        $this->lock_file = $this->fs->path('/path/to/lock_file.lock');

        mkdir(dirname($this->lock_file), 0777, true);

        $this->beConstructedWith($this->lock_file);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('TH\Lock\FileLock');
        $this->shouldImplement('TH\Lock\Lock');
    }

    public function it_should_acquire_an_exclusive_lock()
    {
        $this->acquire();

        if (!file_exists($this->lock_file)) {
            throw new Exception('Lock file was not created');
        }

        if (flock(fopen($this->lock_file, 'r'), LOCK_SH|LOCK_NB)) {
            throw new Exception('Lock file was not exclusively locked');
        }
    }

    public function it_should_acquire_an_shared_lock()
    {
        $this->acquire(FileLock::SHARED);

        if (!file_exists($this->lock_file)) {
            throw new Exception('Lock file was not created');
        }

        if (flock(fopen($this->lock_file, 'r'), LOCK_EX|LOCK_NB)) {
            throw new Exception('Lock file was not locked againt exclusive lock');
        }

        if (!flock(fopen($this->lock_file, 'r'), LOCK_SH|LOCK_NB)) {
            throw new Exception('Lock file was not shared locked');
        }
    }

    public function it_should_release_a_lock()
    {
        $this->acquire(FileLock::SHARED);
        $this->release();

        touch($this->lock_file);

        if (!flock(fopen($this->lock_file, 'r'), LOCK_EX|LOCK_NB)) {
            throw new Exception('Lock file was not released');
        }
    }

    public function it_should_throw_if_it_cannot_lock()
    {
        touch($this->lock_file);
        flock(fopen($this->lock_file, 'r'), LOCK_SH|LOCK_NB);

        $this->shouldThrow()->duringAcquire();
    }

    public function it_remove_its_lock_file_if_not_locked()
    {
        $this->acquire();
        $this->release();

        if (file_exists($this->lock_file)) {
            throw new Exception('Lock file was not removed');
        }
    }

    public function it_does_not_remove_its_lock_file_if_still_locked()
    {
        touch($this->lock_file);
        flock(fopen($this->lock_file, 'r'), LOCK_SH|LOCK_NB);

        $this->acquire(FileLock::SHARED);
        $this->release();

        if (!file_exists($this->lock_file)) {
            throw new Exception('Lock file was removed');
        }
    }

    public function it_can_acquire_then_release_and_acquire_again()
    {
        $this->acquire();
        $this->release();
        $this->acquire();

        if (!file_exists($this->lock_file)) {
            throw new Exception('Lock file was not created');
        }

        if (flock(fopen($this->lock_file, 'r'), LOCK_SH|LOCK_NB)) {
            throw new Exception('Lock file was not exclusively locked');
        }
    }
}
