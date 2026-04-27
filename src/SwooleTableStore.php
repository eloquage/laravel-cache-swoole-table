<?php

namespace IllumaLaw\SwooleTableCache;

use Illuminate\Contracts\Cache\Store;
use Swoole\Table;

class SwooleTableStore implements Store
{
    public function __construct(
        protected Table $table,
        protected string $prefix = ''
    ) {}

    public function get($key): mixed
    {
        $row = $this->table->get($this->prefix.$key);

        if ($row === false) {
            return null;
        }

        if ($row['expiration'] !== 0 && time() >= $row['expiration']) {
            $this->forget($key);

            return null;
        }

        if ($row['is_numeric']) {
            return $row['numeric_value'];
        }

        return unserialize($row['value']);
    }

    public function many(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    public function put($key, $value, $seconds): bool
    {
        $expiration = $seconds > 0 ? time() + (int) $seconds : 0;
        $isNumeric = is_int($value);

        $data = [
            'expiration' => $expiration,
            'is_numeric' => $isNumeric ? 1 : 0,
        ];

        if ($isNumeric) {
            $data['numeric_value'] = $value;
            $data['value'] = '';
        } else {
            $data['numeric_value'] = 0;
            $data['value'] = serialize($value);
        }

        return $this->table->set($this->prefix.$key, $data);
    }

    public function putMany(array $values, $seconds): bool
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $seconds);
        }

        return true;
    }

    public function increment($key, $value = 1): int|bool
    {
        $prefixedKey = $this->prefix.$key;
        $row = $this->table->get($prefixedKey);

        if ($row === false || ! $row['is_numeric']) {
            $this->forever($key, $value);

            return $value;
        }

        return $this->table->incr($prefixedKey, 'numeric_value', (int) $value);
    }

    public function decrement($key, $value = 1): int|bool
    {
        $prefixedKey = $this->prefix.$key;
        $row = $this->table->get($prefixedKey);

        if ($row === false || ! $row['is_numeric']) {
            $this->forever($key, 0 - $value);

            return 0 - $value;
        }

        return $this->table->decr($prefixedKey, 'numeric_value', (int) $value);
    }

    public function forever($key, $value): bool
    {
        return $this->put($key, $value, 0);
    }

    public function forget($key): bool
    {
        return $this->table->del($this->prefix.$key);
    }

    public function flush(): bool
    {
        foreach ($this->table as $key => $row) {
            $this->table->del($key);
        }

        return true;
    }

    public function touch($key, $seconds): bool
    {
        $prefixedKey = $this->prefix.$key;
        $row = $this->table->get($prefixedKey);

        if ($row === false) {
            return false;
        }

        $expiration = $seconds > 0 ? time() + (int) $seconds : 0;

        return $this->table->set($prefixedKey, [
            'value'         => $row['value'],
            'numeric_value' => $row['numeric_value'],
            'is_numeric'    => $row['is_numeric'],
            'expiration'    => $expiration,
        ]);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
