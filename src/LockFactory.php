<?php

namespace TH\Lock;

interface LockFactory
{
    public function create($resource, $owner = null);
}
