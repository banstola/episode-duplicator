<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Contracts\RedisServerInterface;
use App\Service\Exception\LockAcquireFailedException;
use App\Service\RedisLockService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RedisLockServiceTest extends TestCase
{
    private RedisServerInterface&MockObject $redis;

    private RedisLockService $lockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = $this->createMock(RedisServerInterface::class);
        $this->lockService = new RedisLockService($this->redis);
    }

    public function test_acquire_lock_succeeds_when_key_is_available(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('setIfNotExists')
            ->with('my-lock', 1, 3600)
            ->willReturn(true);

        $this->lockService->acquireLock('my-lock', 3600);

        $this->assertTrue(true);
    }

    public function test_acquire_lock_throws_when_key_already_exists(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('setIfNotExists')
            ->with('my-lock', 1, 3600)
            ->willReturn(false);

        $this->expectException(LockAcquireFailedException::class);
        $this->expectExceptionMessage('Lock already exists for key: my-lock');

        $this->lockService->acquireLock('my-lock', 3600);
    }

    public function test_release_lock_deletes_the_key(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('delete')
            ->with('my-lock');

        $this->lockService->releaseLock('my-lock');
    }

    public function test_ttl_is_forwarded_to_redis(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('setIfNotExists')
            ->with('lock:episode:abc', 1, 7200)
            ->willReturn(true);

        $this->lockService->acquireLock('lock:episode:abc', 7200);
    }
}
