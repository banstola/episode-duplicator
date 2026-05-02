<?php

declare(strict_types=1);

namespace App\Service;

use App\Contracts\RedisServerInterface;
use Illuminate\Support\Facades\Redis;

final readonly class RedisServer implements RedisServerInterface
{
    public function setIfNotExists(string $key, mixed $value, int $ttl): bool
    {
        return (bool) Redis::set($key, $value, 'EX', $ttl, 'NX');
    }

    public function delete(string $key): void
    {
        Redis::del($key);
    }

    public function setHashFields(string $key, array $fields): void
    {
        Redis::hmset($key, $fields);
    }

    public function getHashAll(string $key): array
    {
        return Redis::hgetall($key) ?: [];
    }

    public function setExpiry(string $key, int $seconds): void
    {
        Redis::expire($key, $seconds);
    }
}
