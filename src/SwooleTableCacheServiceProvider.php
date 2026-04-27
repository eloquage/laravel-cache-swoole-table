<?php

namespace IllumaLaw\SwooleTableCache;

use IllumaLaw\SwooleTableCache\Exceptions\SwooleExtensionMissingException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Swoole\Table;

class SwooleTableCacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('swoole.table.cache_table', function ($app) {
            if (! extension_loaded('swoole')) {
                throw new SwooleExtensionMissingException('The swoole extension is required for the swoole-table cache driver.');
            }

            $config = $app['config']['cache.stores.swoole-table'] ?? [];
            $tableSize = $config['table_size'] ?? 1024;
            $valueSize = $config['value_size'] ?? 65536;

            $table = new Table($tableSize);
            $table->column('value', Table::TYPE_STRING, $valueSize);
            $table->column('numeric_value', Table::TYPE_INT, 8);
            $table->column('is_numeric', Table::TYPE_INT, 1);
            $table->column('expiration', Table::TYPE_INT);
            $table->create();

            return $table;
        });
    }

    public function boot(): void
    {
        Cache::extend('swoole-table', function ($app, $config) {
            return Cache::repository(
                new SwooleTableStore(
                    $app->make('swoole.table.cache_table'),
                    $config['prefix'] ?? $app['config']['cache.prefix'] ?? ''
                )
            );
        });
    }
}
