<?php

declare(strict_types=1);

namespace App\Service;

use App\Contracts\LockServiceInterface;
use App\Dto\CancelResultTo;
use App\Dto\ScheduleResultTo;
use App\Dto\StatusResultTo;
use App\Helper\LockKeyHelper;
use App\Service\Exception\LockAcquireFailedException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class EpisodeDuplicationService
{
    private const int DUPLICATE_EPISODE_LOCK_TTL = 2 * 60 * 60; // 2 hours

    public function __construct(
        private LoggerInterface $logger,
        private LockServiceInterface $lockService
    ) {}

    public function schedule(string $originalEpisodeUuid): ScheduleResultTo
    {
        /**
         * Steps here
         * 1. Try to acquire lock
         * 2. Set lock
         * 3. Log basic metrics that give an idea on the size of this operation
         * 4. Initiate the first fan-out Job
         * 5. Return the new uuid - for tracking status - cancelling etc
         */
        try {
            $this->lockService->acquireLock(LockKeyHelper::getDuplicateEpisodeKey($originalEpisodeUuid), self::DUPLICATE_EPISODE_LOCK_TTL);
        } catch (LockAcquireFailedException) {
            throw new \RuntimeException('DUPLICATION_IN_PROGRESS');
        }

        $duplicatedEpisodeUuid = Str::uuid7()->toString();

        return new ScheduleResultTo(
            $originalEpisodeUuid,
            $duplicatedEpisodeUuid,
            0,
            0,
            0,
            0,
            0,
        );

    }

    public function getStatus(string $duplicateEpisodeUuid): StatusResultTo
    {

        return new StatusResultTo(
            'progress',
            'original-episode-uuid',
            $duplicateEpisodeUuid,
            null,
            null,
        );

    }

    public function cancel(string $duplicateEpisodeUuid): CancelResultTo
    {
        return new CancelResultTo(
            'original-episode-uuid',
            $duplicateEpisodeUuid,
            Carbon::now('UTC')->format('Y-m-d H:i:s')
        );

    }
}
