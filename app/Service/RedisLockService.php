<?php

declare(strict_types=1);

namespace App\Service;

use App\Contracts\LockServiceInterface;
use App\Contracts\RedisServerInterface;
use App\Service\Exception\LockAcquireFailedException;

final readonly class RedisLockService implements LockServiceInterface
{
    public function __construct(
        private RedisServerInterface $redis,
    ) {}

    public function acquireLock(string $lock, int $ttl): void
    {
        $acquired = $this->redis->setIfNotExists($lock, 1, $ttl);

        if (! $acquired) {
            throw new LockAcquireFailedException("Lock already exists for key: {$lock}");
        }
    }

    public function releaseLock(string $lock): void
    {
        $this->redis->delete($lock);
    }
}
