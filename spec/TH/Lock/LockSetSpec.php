<?php

namespace spec\TH\Lock;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use TH\Lock\Lock;
use TH\Lock\LockSet;
use TH\Lock\RuntimeException;
use VirtualFileSystem\FileSystem;

class LockSetSpec extends ObjectBehavior
{
    public function let(Lock $lock1)
    {
        $this->beConstructedWith([$lock1]);
    }

    public function letgo(Lock $lock1, Lock $lock2)
    {
        foreach (array_filter([$lock1, $lock2]) as $lock) {
            $o = $lock->getWrappedObject()->getProphecy();
            $r = new \ReflectionObject($o);
            $p = $r->getProperty('methodProphecies');
            $p->setAccessible(true);
            $p->setValue($o, []);
        }
    }

    public function it_is_initializable(Lock $lock1)
    {
        $this->shouldHaveType(LockSet::class);
        $this->shouldImplement(Lock::class);
    }

    public function it_should_not_be_empty()
    {
        $this->beConstructedWith([]);
        $this->shouldThrow(new RuntimeException("Lock set cannot be empty"))->duringInstantiation();
    }

    public function it_should_acquire_a_lock(Lock $lock1)
    {
        $lock1->acquire()->shouldBeCalled();
        $this->acquire();
    }

    public function it_should_acquire_all_locks(Lock $lock1, Lock $lock2)
    {
        $this->beConstructedWith([$lock1, $lock2]);
        $this->acquire();
        $lock1->acquire()->shouldHaveBeenCalled();
        $lock2->acquire()->shouldHaveBeenCalled();
        $lock1->release()->shouldBeCalled();
        $lock2->release()->shouldBeCalled();
    }

    public function it_should_fail_to_acquire_if_one_lock_fail(Lock $lock1, Lock $lock2)
    {
        $this->beConstructedWith([$lock1, $lock2]);
        $lock1->acquire()->shouldBeCalled();
        $lock1->release()->shouldBeCalled();
        $lock2->acquire()->shouldBeCalled();
        $lock2->acquire()->willThrow(new RuntimeException);
        $this->shouldThrow(RuntimeException::class)->duringAcquire();
    }

    public function it_should_stop_trying_to_acquire_on_failure(Lock $lock1, Lock $lock2)
    {
        $this->beConstructedWith([$lock1, $lock2]);
        $lock1->acquire()->shouldBeCalled();
        $lock1->acquire()->willThrow(new RuntimeException);
        $lock2->acquire()->shouldNotBeCalled();
        $this->shouldThrow(RuntimeException::class)->duringAcquire();
    }

    public function it_should_release_acquired_lock_on_acquire_failure(Lock $lock1, Lock $lock2)
    {
        $this->beConstructedWith([$lock1, $lock2]);
        $lock1->acquire()->shouldBeCalled();
        $lock1->release()->shouldBeCalled();
        $lock2->acquire()->willThrow(new RuntimeException);
        $this->shouldThrow(RuntimeException::class)->duringAcquire();
    }

    public function it_should_not_release_not_acquired_lock_on_acquire_failure(Lock $lock1, Lock $lock2)
    {
        $this->beConstructedWith([$lock1, $lock2]);
        $lock1->acquire()->shouldBeCalled();
        $lock1->acquire()->willThrow(new RuntimeException);
        $lock1->release()->shouldNotBeCalled();
        $lock2->release()->shouldNotBeCalled();
        $this->shouldThrow(RuntimeException::class)->duringAcquire();
    }

    public function it_should_release_all_locks(Lock $lock1, Lock $lock2)
    {
        $this->beConstructedWith([$lock1, $lock2]);
        $lock1->release()->shouldBeCalled();
        $lock2->release()->shouldBeCalled();
        $this->release();
    }

    public function it_should_release_all_locks_even_if_one_failed(Lock $lock1, Lock $lock2)
    {
        $this->beConstructedWith([$lock1, $lock2]);
        $lock1->release()->shouldBeCalled();
        $lock1->release()->willThrow(new RuntimeException);
        $lock2->release()->shouldBeCalled();
        $this->shouldThrow(RuntimeException::class)->duringRelease();
    }
}
