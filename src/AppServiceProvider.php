<?php

namespace LaravelLiberu\CacheChain;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use LaravelLiberu\CacheChain\Extensions\Chain;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->booting(fn () => Cache::extend(
            'chain',
            fn () => Cache::repository(new Chain())
        ));
    }
}
