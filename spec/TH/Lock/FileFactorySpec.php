<?php

namespace spec\TH\Lock;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use TH\Lock\Factory;
use TH\Lock\FileFactory;
use TH\Lock\FileLock;
use VirtualFileSystem\FileSystem;

class FileFactorySpec extends ObjectBehavior
{
    private $fs;
    private $lock_dir;

    public function let()
    {
        $this->fs = new FileSystem;
        $this->lock_dir = $this->fs->path("/path/to/lock_dir");

        $this->beConstructedWith($this->lock_dir, "sha256");
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FileFactory::class);
        $this->shouldImplement(Factory::class);
    }

    public function it_should_create_a_file_lock()
    {
        $this->create("some resource identifier")->shouldhaveType(FileLock::class);
    }
}
