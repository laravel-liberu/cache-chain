<?php

use Illuminate\Support\Collection;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Cache\Store;

class ChainTest extends TestCase
{
    /** @test */
    public function can_put_on_all_adapters()
    {
        $adapters = $this->setAdapters('array', 'file');

        Cache::store('chain')->put('foo', 'bar');

        (new Collection(['file', 'array', 'chain']))->each(fn ($provider) => $this
            ->assertEquals('bar', Cache::store($provider)->get('foo')));
    }

    /** @test */
    public function should_get_on_first()
    {
        $adapters = $this->setAdapters('array', Mockery::mock(Store::class));

        Cache::store('array')->put('foo', 'bar');

        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
    }

    /** @test */
    public function when_cache_exists_one_second_level_should_put_one_first_level()
    {
        $adapters = $this->setAdapters('array', 'file');

        Cache::store('file')->put('foo', 'bar');

        $this->assertNull(Cache::store('array')->get('foo'));

        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
        $this->assertEquals('bar', Cache::store('array')->get('foo'));
    }

    /** @test */
    public function can_get_on_second()
    {
        $adapters = $this->setAdapters(Mockery::spy(Store::class), 'array');

        Cache::store('array')->put('foo', 'bar');

        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
    }

    /** @test */
    public function can_flush()
    {
        $adapters = $this->setAdapters('file', 'array');

        Cache::store('chain')->put('foo', 'bar');
        Cache::store('chain')->put('bar', 'foo');
        Cache::store('chain')->flush();

        (new Collection(['file', 'array', 'chain']))
            ->each(fn ($provider) => $this
                ->assertFalse(Cache::store($provider)->has('foo')))
            ->each(fn ($provider) => $this
                ->assertFalse(Cache::store($provider)->has('bar')));
    }

    /** @test */
    public function can_forget()
    {
        $adapters = $this->setAdapters('file', 'array');

        Cache::store('chain')->put('foo', 'bar');
        Cache::store('chain')->put('bar', 'foo');
        Cache::store('chain')->forget('foo');

        (new Collection(['file', 'array', 'chain']))
            ->each(fn ($provider) => $this
                ->assertFalse(Cache::store($provider)->has('foo')))
            ->each(fn ($provider) => $this
                ->assertTrue(Cache::store($provider)->has('bar')));
    }

    /** @test */
    public function can_increment()
    {
        $adapters = $this->setAdapters('file', 'array');

        Cache::store('chain')->put('number', 1);
        Cache::store('chain')->increment('number', 2);

        (new Collection(['file', 'array', 'chain']))->each(fn ($provider) => $this
            ->assertEquals(3, Cache::store($provider)->get('number')));
    }

    /** @test */
    public function can_decrement()
    {
        $adapters = $this->setAdapters('file', 'array');

        Cache::store('chain')->put('number', 1);
        Cache::store('chain')->decrement('number', 2);

        (new Collection(['file', 'array', 'chain']))->each(fn ($provider) => $this
            ->assertEquals(-1, Cache::store($provider)->get('number')));
    }

    protected function tearDown(): void
    {
        Cache::store('file')->flush();

        parent::tearDown();
    }

    private function setAdapters(...$adapters): array
    {
        Config::set('cache.stores.chain.adapters', $adapters);

        return $adapters;
    }
}
