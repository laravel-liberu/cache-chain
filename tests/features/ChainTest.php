<?php

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use LaravelLiberu\CacheChain\Exceptions\Chain as Exception;
use Tests\TestCase;

class ChainTest extends TestCase
{
    /** @test */
    public function should_cache_on_all_configured_providers()
    {
        $providers = ['array', 'file'];
        Cache::store('chain')->providers($providers);

        $this->assertTrue(Cache::store('chain')->put('foo', 'bar'));

        Collection::wrap($providers)->each(fn ($provider) => $this
            ->assertEquals('bar', Cache::store($provider)->get('foo')));
    }

    /** @test */
    public function should_get_from_first_layer_when_available()
    {
        Cache::store('chain')->providers(['array', new Repository(Mockery::mock(Store::class))]);

        $this->assertTrue(Cache::store('array')->put('foo', 'bar'));

        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
    }

    /** @test */
    public function should_get_from_superior_layer_when_first_not_available()
    {
        Cache::store('chain')->providers([new Repository(Mockery::spy(Store::class)), 'array']);

        $this->assertTrue(Cache::store('array')->put('foo', 'bar'));

        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
    }

    /** @test */
    public function should_cache_inferior_layers_on_get_when_superior_exists()
    {
        Cache::store('chain')->providers(['array', 'file']);

        Cache::store('file')->put('foo', 'bar');

        $this->assertNull(Cache::store('array')->get('foo'));
        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
        $this->assertEquals('bar', Cache::store('array')->get('foo'));
    }

    /** @test */
    public function should_sync_inferior_layers_when_superior_exists()
    {
        Cache::store('chain')->providers(['array', 'file']);

        Cache::store('file')->put('foo', 5);

        $this->assertEquals(6, Cache::store('chain')->increment('foo'));
        $this->assertEquals(6, Cache::store('array')->get('foo'));
    }

    /** @test */
    public function can_flush()
    {
        $providers = ['file', 'array'];
        Cache::store('chain')->providers($providers);

        Cache::store('chain')->put('foo', 'bar');
        Cache::store('chain')->put('bar', 'foo');
        $this->assertTrue(Cache::store('chain')->flush());

        Collection::wrap($providers)->each(fn ($provider) => tap($this)
            ->assertFalse(Cache::store($provider)->has('foo'))
            ->assertFalse(Cache::store($provider)->has('bar')));
    }

    /** @test */
    public function can_forget()
    {
        $providers = ['file', 'array'];
        Cache::store('chain')->providers($providers);

        Cache::store('chain')->put('foo', 'bar');
        Cache::store('chain')->put('bar', 'foo');
        $this->assertTrue(Cache::store('chain')->forget('foo'));

        Collection::wrap($providers)->each(fn ($provider) => tap($this)
            ->assertFalse(Cache::store($provider)->has('foo'))
            ->assertTrue(Cache::store($provider)->has('bar')));
    }

    /** @test */
    public function can_increment()
    {
        $providers = ['file', 'array'];
        Cache::store('chain')->providers($providers);

        Cache::store('chain')->put('number', 1);
        $this->assertEquals(3, Cache::store('chain')->increment('number', 2));

        Collection::wrap($providers)->each(fn ($provider) => $this
            ->assertEquals(3, Cache::store($provider)->get('number')));
    }

    /** @test */
    public function can_decrement()
    {
        $providers = ['file', 'array'];
        Cache::store('chain')->providers($providers);

        Cache::store('chain')->put('number', 3);
        $this->assertEquals(1, Cache::store('chain')->decrement('number', 2));

        Collection::wrap($providers)->each(fn ($provider) => $this
            ->assertEquals(1, Cache::store($provider)->get('number')));
    }

    /** @test */
    public function should_throw_exception_with_empty_providers()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::providers()->getMessage());
        Cache::store('chain')->providers([]);
    }

    /** @test */
    public function should_throw_exception_with_empty_lock_providers()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::lockProvider()->getMessage());
        Cache::store('chain')->providers([new Repository(Mockery::mock(Store::class))]);
        Cache::store('chain')->lock('test');
    }

    protected function tearDown(): void
    {
        Cache::store('file')->flush();

        parent::tearDown();
    }
}
