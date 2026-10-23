# Laravel Swoole Table Cache

![Laravel Swoole Table Cache package banner](art/banner.png)

A Laravel cache driver backed by [`Swoole\Table`](https://openswoole.com/docs/modules/swoole-table). It is intended for persistent Swoole or Octane workers that need a fast, process-shared in-memory cache.

Requires PHP 8.3+ (8.3, 8.4, or 8.5), Laravel 11, 12, or 13, and the Swoole PHP extension.

## Install

```bash
composer require illuma-law/laravel-cache-swoole-table
```

Add a store to `config/cache.php`:

```php
'stores' => [
    'swoole-table' => [
        'driver' => 'swoole-table',
        'table_size' => 1024, // Maximum number of cache entries
        'value_size' => 65536, // Maximum serialized value size in bytes
    ],
],
```

Use it like another Laravel cache store:

```php
use Illuminate\Support\Facades\Cache;

$cache = Cache::store('swoole-table');
$cache->put('visits', 1, 60);
$cache->increment('visits');

$count = $cache->get('visits'); // 2
```

You can make it the default store with `CACHE_STORE=swoole-table`.

## Notes

- Entries live in memory and are lost when the Swoole table is recreated. This is not a persistent or cross-host cache.
- `table_size` sets the row capacity. Choose enough rows for the number of keys the application will store.
- `value_size` must fit the serialized value. `Swoole\Table::set()` can fail when the table is full or a value exceeds its configured column width.
- TTL expiration is checked when a key is read.

## Tests

```bash
composer install
composer test
```

The test environment must have the Swoole extension enabled.

## License

MIT. See [LICENSE.md](LICENSE.md).
