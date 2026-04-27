<?php

declare(strict_types=1);

namespace IllumaLaw\SwooleTableCache\Tests\Feature;

use IllumaLaw\SwooleTableCache\SwooleTableCacheServiceProvider;
use IllumaLaw\SwooleTableCache\SwooleTableStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class SwooleCacheDriverTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [SwooleTableCacheServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('cache.stores.swoole-table', [
            'driver'     => 'swoole-table',
            'table_size' => 1024,
            'value_size' => 65536,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('swoole')) {
            $this->markTestSkipped('The swoole extension is required for this test.');
        }
    }

    public function test_registers_swoole_table_driver()
    {
        $this->assertInstanceOf(Repository::class, Cache::store('swoole-table'));
        $this->assertInstanceOf(SwooleTableStore::class, Cache::store('swoole-table')->getStore());
    }

    public function test_can_put_and_get_values()
    {
        Cache::store('swoole-table')->put('key', 'value', 60);
        $this->assertEquals('value', Cache::store('swoole-table')->get('key'));
    }

    public function test_handles_numeric_values_separately()
    {
        $store = Cache::store('swoole-table');
        $store->put('num', 123, 60);

        $this->assertEquals(123, $store->get('num'));
        $this->assertIsInt($store->get('num'));
    }

    public function test_atomic_increment_decrement()
    {
        $store = Cache::store('swoole-table');
        $store->put('counter', 10, 60);

        $this->assertEquals(11, $store->increment('counter'));
        $this->assertEquals(15, $store->increment('counter', 4));
        $this->assertEquals(10, $store->decrement('counter', 5));
    }

    public function test_expiration_works()
    {
        $store = Cache::store('swoole-table');
        $store->put('expiring', 'value', 1);
        $this->assertEquals('value', $store->get('expiring'));

        sleep(2);
        $this->assertNull($store->get('expiring'));
    }

    public function test_many_operations()
    {
        $store = Cache::store('swoole-table');
        $store->putMany(['a' => 1, 'b' => 2], 60);

        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => null], $store->many(['a', 'b', 'c']));
    }

    public function test_forget_and_flush()
    {
        $store = Cache::store('swoole-table');
        $store->forever('f1', 'v1');
        $store->forget('f1');
        $this->assertNull($store->get('f1'));

        $store->forever('f2', 'v2');
        $store->flush();
        $this->assertNull($store->get('f2'));
    }
}
