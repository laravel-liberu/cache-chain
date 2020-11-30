<?php

namespace LaravelEnso\CacheChain\Exceptions;

use Exception;

class Chain extends Exception
{
    public static function providers(): self
    {
        return new static(__('No cache adapters provided'));
    }

    public static function lockProvider(): self
    {
        return new static(__('No lock cache adapters provided'));
    }
}
