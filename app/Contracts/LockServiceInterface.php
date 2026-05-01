<?php

declare(strict_types=1);

namespace App\Contracts;

interface LockServiceInterface
{
    public function acquireLock(string $lock, int $ttl): void
    ;

    public function releaseLock(string $lock): void;
}
