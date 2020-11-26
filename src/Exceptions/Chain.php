<?php

namespace LaravelEnso\CacheChain\Exceptions;

use Exception;

class Chain extends Exception
{
    public static function emptryAdapaters(): self
    {
        return new static(__('You should at least provide one adapter'));
    }
}
