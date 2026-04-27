# Laravel Swoole Table Cache

A Laravel Cache driver using `Swoole\Table` for high-performance shared memory storage.

This driver is ideal for applications running under Swoole or Laravel Octane that need a fast, shared memory cache with minimal overhead.

## Features

- **High Performance:** Uses `Swoole\Table` which is implemented in C and resides in shared memory.
- **Atomic Operations:** Supports atomic `increment` and `decrement` via Swoole's native `Table::incr` and `Table::decr`.
- **Octane Ready:** Perfect for sharing state between Octane workers.
- **TTL Support:** Handles expiration automatically within the driver.

## Requirements

- PHP 8.3 or newer
- Laravel 11, 12, or 13
- The `swoole` PHP extension enabled

## Installation

1. **Require the package** with Composer:

```shell
composer require illuma-law/laravel-cache-swoole-table
```

## Step-by-Step Setup

### 1. Configure the Cache Store

Add the `swoole-table` store to your `config/cache.php` file:

```php
'stores' => [
    // ... other stores
    
    'swoole-table' => [
        'driver' => 'swoole-table',
        'table_size' => env('SWOOLE_CACHE_TABLE_SIZE', 1024), // Number of rows
        'value_size' => env('SWOOLE_CACHE_VALUE_SIZE', 65536), // Max size of serialized value in bytes
    ],
],
```

### 2. Set as Default (Optional)

Update your `.env` file to use Swoole Table as the default cache store:

```env
CACHE_STORE=swoole-table
```

### 3. Verify Installation

You can verify the driver is working by using Artisan tinker:

```shell
php artisan tinker
```

```php
use Illuminate\Support\Facades\Cache;

Cache::store('swoole-table')->put('test', 'it works', 10);
echo Cache::store('swoole-table')->get('test'); // "it works"
```

## Configuration Options

- `table_size`: The maximum number of rows the table can hold. Must be a power of 2.
- `value_size`: The maximum size (in bytes) for a single cached item. Exceeding this will cause truncation or failure.

## Usage

Use the driver like any other Laravel cache store:

```php
use Illuminate\Support\Facades\Cache;

// Using the default store (if set to swoole-table)
Cache::put('key', 'value', 60);

// Explicitly using the swoole-table store
Cache::store('swoole-table')->put('visits', 1, 60);
Cache::store('swoole-table')->increment('visits');
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
