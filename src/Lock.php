<?php

namespace TH\Lock;

interface Lock
{
    public function acquire();
    public function release();
}
