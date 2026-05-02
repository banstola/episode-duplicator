<?php

declare(strict_types=1);

namespace App\Service;

use App\Contracts\LockServiceInterface;
use App\Service\Exception\LockAcquireFailedException;
use Illuminate\Support\Facades\Redis;

final readonly class RedisLockService implements LockServiceInterface
{
    public function acquireLock(string $lock, int $ttl): void
    {
        $acquired = Redis::set($lock, 1, 'EX', $ttl, 'NX');

        if (! $acquired) {
            throw new LockAcquireFailedException("Lock already exists for key: {$lock}");
        }

    }

    public function releaseLock(string $lock): void
    {
        Redis::del($lock);
    }
}
