<?php

namespace LaravelEnso\CacheChain\Extensions;

use Illuminate\Cache\RetrievesMultipleKeys;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\InteractsWithTime;

class Chain extends TaggableStore
{
    use InteractsWithTime, RetrievesMultipleKeys;

    private Collection $adapters;
    private ?int $ttl;

    public function __construct()
    {
        $this->adapters(Config::get('cache.stores.chain.adapters'));

        $this->ttl = Config::get('cache.stores.chain.defaultTTL');
    }

    public function get($key)
    {
        return $this->cacheGet($key);
    }

    public function put($key, $value, $seconds)
    {
        return $this->and('put', ...func_get_args());
    }

    public function increment($key, $value = 1)
    {
        return $this->last('increment', ...func_get_args());
    }

    public function decrement($key, $value = 1)
    {
        return $this->last('decrement', ...func_get_args());
    }

    public function forever($key, $value)
    {
        return $this->and('forever', ...func_get_args());
    }

    public function forget($key)
    {
        return $this->and('forget', ...func_get_args());
    }

    public function flush()
    {
        return $this->and('flush', ...func_get_args());
    }

    public function getPrefix()
    {
        return '';
    }

    public function adapters(array $adapters)
    {
        $this->adapters = Collection::wrap($adapters)
            ->map(fn ($provider) => $this->store($provider));
    }

    private function last($method, ...$args)
    {
        return $this->adapters->reduce(fn($tmp, $adapter) => $adapter->{$method}(...$args));
    }

    private function or($method, ...$args)
    {
        return $this->adapters->reduce(fn($result, $adapter) => $adapter->{$method}(...$args) || $result, false);
    }

    private function and($method, ...$args)
    {
        return $this->adapters->reduce(fn($result, $adapter) => $adapter->{$method}(...$args) && $result, true);
    }

    private function cacheGet($key, int $layer = 0)
    {
        if ($layer >= $this->adapters->count()) {
            return;
        }

        if ($cachedValue = $this->adapters->get($layer)->get($key)) {
            return $cachedValue;
        }

        if ($cachedValue = $this->cacheGet($key, $layer + 1)) {
            if ($this->ttl > 0) {
                $this->adapters->get($layer)->put($key, $cachedValue, $this->ttl);
            } else {
                $this->adapters->get($layer)->forever($key, $cachedValue);
            }
        }

        return $cachedValue;
    }

    private function store($provider)
    {
        return $provider instanceof Store
            ? $provider
            : Cache::store($provider);
    }
}
