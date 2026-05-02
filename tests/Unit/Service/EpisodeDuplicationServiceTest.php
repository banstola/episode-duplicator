<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Contracts\LockServiceInterface;
use App\Contracts\RedisServerInterface;
use App\Helper\DuplicationRedisField;
use App\Helper\DuplicationStatus;
use App\Helper\LogEvent;
use App\Service\EpisodeDuplicationService;
use App\Service\Exception\LockAcquireFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EpisodeDuplicationServiceTest extends TestCase
{
    private LoggerInterface&MockObject $logger;

    private LockServiceInterface&MockObject $lockService;

    private RedisServerInterface&MockObject $redis;

    private EpisodeDuplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->lockService = $this->createMock(LockServiceInterface::class);
        $this->redis = $this->createMock(RedisServerInterface::class);

        $this->service = new EpisodeDuplicationService(
            $this->logger,
            $this->lockService,
            $this->redis,
        );
    }

    public function test_schedule_throws_when_lock_cannot_be_acquired(): void
    {
        $this->lockService
            ->expects($this->once())
            ->method('acquireLock')
            ->willThrowException(new LockAcquireFailedException('already locked'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(LogEvent::DUPLICATION_IN_PROGRESS);

        $this->service->schedule('some-episode-uuid');
    }

    public function test_get_status_returns_data_when_duplication_exists(): void
    {
        $duplicationId = 'test-duplication-id';

        $this->redis
            ->expects($this->once())
            ->method('getHashAll')
            ->willReturn([
                DuplicationRedisField::STATUS => DuplicationStatus::PROCESSING,
                DuplicationRedisField::SOURCE_EPISODE_UUID => 'source-uuid',
                DuplicationRedisField::NEW_EPISODE_UUID => $duplicationId,
                DuplicationRedisField::STARTED_AT => '2026-05-01 12:00:00',
            ]);

        $result = $this->service->getStatus($duplicationId);

        $this->assertSame(DuplicationStatus::PROCESSING, $result->status);
        $this->assertSame('source-uuid', $result->originalEpisodeUuid);
        $this->assertSame($duplicationId, $result->newEpisodeUuid);
        $this->assertSame('2026-05-01 12:00:00', $result->startedAt);
        $this->assertNull($result->completedAt);
    }

    public function test_get_status_throws_when_duplication_not_found(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('getHashAll')
            ->willReturn([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(LogEvent::DUPLICATION_NOT_FOUND);

        $this->service->getStatus('nonexistent-id');
    }

    public function test_cancel_throws_when_duplication_not_found(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('getHashAll')
            ->willReturn([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(LogEvent::DUPLICATION_NOT_FOUND);

        $this->service->cancel('nonexistent-id');
    }
}
