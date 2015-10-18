# php-lock

[![Build Status](https://travis-ci.org/texthtml/php-lock.svg?branch=master)](https://travis-ci.org/texthtml/php-lock)
[![Latest Stable Version](https://poser.pugx.org/texthtml/php-lock/v/stable.svg)](https://packagist.org/packages/texthtml/php-lock)
[![License](https://poser.pugx.org/texthtml/php-lock/license.svg)](http://www.gnu.org/licenses/agpl-3.0.html)
[![Total Downloads](https://poser.pugx.org/texthtml/php-lock/downloads.svg)](https://packagist.org/packages/texthtml/php-lock)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/texthtml/php-lock/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/texthtml/php-lock/?branch=master)

[php-lock](https://packagist.org/packages/texthtml/php-lock) is a library that makes locking on resources easy. It can be used to avoid access to a file during write operations or to prevent crontabs to overlap. And it's designed to integrate well with Dependancy Injection (eg Symfony Container or Pimple).

## Installation

With Composer :

```bash
composer require texthtml/php-lock
```

## Usage

You can create an object that represent a lock on a file. You can then try to acquire that lock by calling `$lock->acquire()`. If the lock fail it will throw an `Exception` (useful for CLI tools built with [Symfony Console Components documentation](http://symfony.com/doc/current/components/console/introduction.html)). If the lock is acquired the program can continue.

### Locking a file exclusively

```php
use TH\Lock\FileLock;

$lock = new FileLock('/path/to/file');

$lock->acquire();

// other processes that try to acquire a lock on the file will fail

// edit /path/to/file

$lock->release();

// other processes can now acquire a lock on the file
```

### Sharing a lock on a file

```php

use TH\Lock\FileLock;

$lock = new FileLock('/path/to/file');

$lock->acquire(FileLock::SHARED);

// other processes that try to acquire an exclusive lock on the file will fail,
// processes that try to acquire an shared lock on the file will succeed

// read /path/to/file

$lock->acquire();

// other processes can now acquire an exclusive lock on the file if no other shared lock remains.
```

### Upgrading or downgrading a lock

`$lock->acquire()` can be called multiple times to either upgrade a shared lock to an exclusive lock or downgrade an exclusive lock to a shared one. You can use it when you're done editing the file.

### Auto release

`$lock->release()` is called automatically when the lock is destroyed so you don't need to manually release it at the end of a script or if it got out of scope.

```php
use TH\Lock\FileLock;

function batch() {
    $lock = new FileLock('/some/file');
    $lock->acquire();

    // lot of editing on file
}

batch();

// the lock will be released here even if $lock->release() is not called in batch()
```

### Using lock for crontabs

When you don't want some crontabs to overlap you can make a lock on the same file in each crontab. The `TH\Lock\LockFactory` can ease the process and provide more helpful message in case of overlap.

```
$lock = $factory->create('protected resource', 'process 1');

$lock->acquire();

// process 1 does stuff
```

```
$lock = $factory->create('protected resource', 'process 2');

$lock->acquire();

// process 2 does stuff
```

When process 1 is running and we start process 2, an Exception will be thrown: "Could not acquire exclusive lock on protected resource" and if the factory was configured with a `\Psr\Log\LoggerInterface`, messages explaining what happend would be logged:

    process 1: exclusive lock acquired on protected resource
    process 2: could not acquire exclusive lock on protected resource
    process 2: lock released on protected resource

The only `LockFactory` available at the moment is the `TH\Lock\FileFactory`. This factory autmatically create lock files for your resources in the specified folder.

```php

use TH\Lock\FileFactory;

$factory = new FileFactory('/path/to/lock_dir/');
$lock = $factory->create('resource identifier');
```

## API

There are two methods you can use on a `FileLock`:

* `\TH\Lock\FileLock::acquire($exclusive = FileLock::EXCLUSIVE, $blocking = FileLock::NON_BLOCKING)` used to acquire a lock on the file
* `\TH\Lock\FileLock::release()` used to release a lock on the file

And one on a `FileFactory':

* `\TH\Lock\FileFactory::create($resource, $owner = null)` used to create a `FileLock` for $resource

## Notes

Only lock file are currently supported. It means distributed processes can't be lock this way. It should be easy to implements the `TH\Lock\Lock` and `TH\Lock\LockFactory` interface to use a distributed lock mechanism (maybe with [Redis](http://redis.io/topics/distlock)).
