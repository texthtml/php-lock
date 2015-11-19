<?php

namespace spec\TH\Lock;

use VirtualFileSystem\FileSystem;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FileFactorySpec extends ObjectBehavior
{
    private $fs;
    private $lock_dir;

    public function let()
    {
        $this->fs = new FileSystem;
        $this->lock_dir = $this->fs->path('/path/to/lock_dir');

        $this->beConstructedWith($this->lock_dir, 'sha256');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('TH\Lock\FileFactory');
        $this->shouldImplement('TH\Lock\Factory');
    }

    public function it_should_create_a_file_lock()
    {
        $this->create('some resource identifier')->shouldhaveType('TH\Lock\FileLock');
    }
}
