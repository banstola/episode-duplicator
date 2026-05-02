<?php

declare(strict_types=1);

namespace App\Service;

use App\Contracts\LockServiceInterface;
use App\Contracts\RedisServerInterface;
use App\Dto\CancelResultTo;
use App\Dto\ScheduleResultTo;
use App\Dto\StatusResultTo;
use App\Helper\DuplicationRedisField;
use App\Helper\DuplicationStatus;
use App\Helper\LockKeyHelper;
use App\Helper\LogEvent;
use App\Jobs\CleanupDuplicationJob;
use App\Jobs\DuplicateEpisodeJob;
use App\Models\BlockFields;
use App\Models\Blocks;
use App\Models\Episodes;
use App\Models\Items;
use App\Models\Media;
use App\Models\Parts;
use App\Service\Exception\LockAcquireFailedException;
use Illuminate\Bus\Batch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class EpisodeDuplicationService
{
    private const int DUPLICATE_EPISODE_LOCK_TTL = 2 * 60 * 60;

    private const int STATUS_TTL_SECONDS = 86400;

    public function __construct(
        private LoggerInterface $logger,
        private LockServiceInterface $lockService,
        private RedisServerInterface $redis,
    ) {}

    public function schedule(string $originalEpisodeUuid): ScheduleResultTo
    {
        $lockKey = LockKeyHelper::getDuplicateEpisodeKey($originalEpisodeUuid);

        try {
            $this->lockService->acquireLock($lockKey, self::DUPLICATE_EPISODE_LOCK_TTL);
        } catch (LockAcquireFailedException) {
            throw new \RuntimeException(LogEvent::DUPLICATION_IN_PROGRESS);
        }

        Episodes::where(Episodes::EPISODE_UUID, $originalEpisodeUuid)->firstOrFail();

        $partUuidsQuery = Parts::where(Parts::EPISODE_UUID, $originalEpisodeUuid)->select(Parts::PART_UUID);
        $itemUuidsQuery = Items::whereIn(Items::PART_UUID, $partUuidsQuery)->select(Items::ITEM_UUID);
        $blockUuidsQuery = Blocks::whereIn(Blocks::ITEM_UUID, $itemUuidsQuery)->select(Blocks::BLOCK_UUID);

        $totalParts = Parts::where(Parts::EPISODE_UUID, $originalEpisodeUuid)->count();
        $totalItems = Items::whereIn(Items::PART_UUID, $partUuidsQuery)->count();
        $totalBlocks = Blocks::whereIn(Blocks::ITEM_UUID, $itemUuidsQuery)->count();
        $totalBlockFields = BlockFields::whereIn(BlockFields::BLOCK_UUID, $blockUuidsQuery)->count();
        $totalMedia = Media::whereIn(Media::BLOCK_UUID, $blockUuidsQuery)->count();

        $newEpisodeUuid = Str::uuid7()->toString();

        $this->logger->info(LogEvent::DUPLICATION_SCHEDULED, [
            'source_episode_uuid' => $originalEpisodeUuid,
            'new_episode_uuid' => $newEpisodeUuid,
            'total_parts' => $totalParts,
            'total_items' => $totalItems,
            'total_blocks' => $totalBlocks,
            'total_block_fields' => $totalBlockFields,
            'total_media' => $totalMedia,
        ]);

        $batch = Bus::batch([
            new DuplicateEpisodeJob(
                sourceEpisodeUuid: $originalEpisodeUuid,
                newEpisodeUuid: $newEpisodeUuid,
            ),
        ])
            ->name(\sprintf('%s_%s', 'duplicate_episode', $originalEpisodeUuid))
            ->then(function (Batch $batch) use ($newEpisodeUuid) {
                Episodes::where(Episodes::EPISODE_UUID, $newEpisodeUuid)
                    ->update([Episodes::STATUS => 'active']);

                $redis = app(RedisServerInterface::class);
                $statusKey = LockKeyHelper::getDuplicationStatusKey($newEpisodeUuid);
                $redis->setHashFields($statusKey, [
                    DuplicationRedisField::STATUS => DuplicationStatus::COMPLETED,
                    DuplicationRedisField::COMPLETED_AT => Carbon::now('UTC')->format('Y-m-d H:i:s'),
                ]);
                $redis->setExpiry($statusKey, self::STATUS_TTL_SECONDS);

                Log::info(LogEvent::DUPLICATION_COMPLETED, [
                    'duplication_id' => $newEpisodeUuid,
                    'batch_id' => $batch->id,
                ]);
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($newEpisodeUuid) {
                Log::error(LogEvent::DUPLICATION_FAILED, [
                    'duplication_id' => $newEpisodeUuid,
                    'error' => $e->getMessage(),
                ]);
                CleanupDuplicationJob::dispatch(
                    episodeUuid: $newEpisodeUuid,
                );
            })
            ->finally(function () use ($lockKey) {
                app(LockServiceInterface::class)->releaseLock($lockKey);
            })
            ->dispatch();

        $statusKey = LockKeyHelper::getDuplicationStatusKey($newEpisodeUuid);
        $this->redis->setHashFields($statusKey, [
            DuplicationRedisField::STATUS => DuplicationStatus::PROCESSING,
            DuplicationRedisField::SOURCE_EPISODE_UUID => $originalEpisodeUuid,
            DuplicationRedisField::NEW_EPISODE_UUID => $newEpisodeUuid,
            DuplicationRedisField::BATCH_ID => $batch->id,
            DuplicationRedisField::STARTED_AT => Carbon::now('UTC')->format('Y-m-d H:i:s'),
        ]);

        return new ScheduleResultTo(
            originalEpisodeUuid: $originalEpisodeUuid,
            duplicatedEpisodeUuid: $newEpisodeUuid,
            totalParts: $totalParts,
            totalItems: $totalItems,
            totalBlocks: $totalBlocks,
            totalBlockFields: $totalBlockFields,
            totalMedia: $totalMedia,
        );
    }

    public function getStatus(string $duplicationId): StatusResultTo
    {
        $statusKey = LockKeyHelper::getDuplicationStatusKey($duplicationId);
        $data = $this->redis->getHashAll($statusKey);

        if (empty($data)) {
            throw new \RuntimeException(LogEvent::DUPLICATION_NOT_FOUND);
        }

        $totalJobs = 0;
        $pendingJobs = 0;
        $failedJobs = 0;
        $progressPercent = null;

        if (! empty($data[DuplicationRedisField::BATCH_ID])) {
            $batch = Bus::findBatch($data[DuplicationRedisField::BATCH_ID]);
            if ($batch) {
                $totalJobs = $batch->totalJobs;
                $pendingJobs = $batch->pendingJobs;
                $failedJobs = $batch->failedJobs;
                $progressPercent = $batch->progress();
            }
        }

        return new StatusResultTo(
            status: $data[DuplicationRedisField::STATUS] ?? DuplicationStatus::UNKNOWN,
            originalEpisodeUuid: $data[DuplicationRedisField::SOURCE_EPISODE_UUID] ?? '',
            newEpisodeUuid: $data[DuplicationRedisField::NEW_EPISODE_UUID] ?? $duplicationId,
            startedAt: $data[DuplicationRedisField::STARTED_AT] ?? null,
            completedAt: $data[DuplicationRedisField::COMPLETED_AT] ?? null,
            totalJobs: $totalJobs,
            pendingJobs: $pendingJobs,
            failedJobs: $failedJobs,
            progressPercent: $progressPercent,
        );
    }

    public function cancel(string $duplicationId): CancelResultTo
    {
        $statusKey = LockKeyHelper::getDuplicationStatusKey($duplicationId);
        $data = $this->redis->getHashAll($statusKey);

        if (empty($data)) {
            throw new \RuntimeException(LogEvent::DUPLICATION_NOT_FOUND);
        }

        if (! empty($data[DuplicationRedisField::BATCH_ID])) {
            $batch = Bus::findBatch($data[DuplicationRedisField::BATCH_ID]);
            $batch?->cancel();
        }

        CleanupDuplicationJob::dispatch(
            episodeUuid: $data[DuplicationRedisField::NEW_EPISODE_UUID] ?? $duplicationId,
        );

        $cancelledAt = Carbon::now('UTC')->format('Y-m-d H:i:s');
        $this->redis->setHashFields($statusKey, [
            DuplicationRedisField::STATUS => DuplicationStatus::CANCELLED,
            DuplicationRedisField::CANCELLED_AT => $cancelledAt,
        ]);
        $this->redis->setExpiry($statusKey, self::STATUS_TTL_SECONDS);

        $this->logger->info(LogEvent::DUPLICATION_CANCELLED, [
            'duplication_id' => $duplicationId,
        ]);

        return new CancelResultTo(
            originalEpisodeUuid: $data[DuplicationRedisField::SOURCE_EPISODE_UUID] ?? '',
            status: DuplicationStatus::CANCELLED,
            cancelledAt: $cancelledAt,
        );
    }
}
