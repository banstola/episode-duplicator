<?php

declare(strict_types=1);

namespace App\Service;

use App\Contracts\LockServiceInterface;
use App\Dto\CancelResultTo;
use App\Dto\ScheduleResultTo;
use App\Dto\StatusResultTo;
use App\Helper\LockKeyHelper;
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
use Illuminate\Support\Facades\Redis;
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
        $lockKey = LockKeyHelper::getDuplicateEpisodeKey($originalEpisodeUuid);

        try {
            $this->lockService->acquireLock($lockKey, self::DUPLICATE_EPISODE_LOCK_TTL);
        } catch (LockAcquireFailedException) {
            throw new \RuntimeException('DUPLICATION_IN_PROGRESS');
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

        $this->logger->info('EPISODE_DUPLICATION_SCHEDULED', [
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
                $statusKey = LockKeyHelper::getDuplicationStatusKey($newEpisodeUuid);
                Redis::hmset($statusKey, [
                    'status' => 'completed',
                    'completed_at' => Carbon::now('UTC')->format('Y-m-d H:i:s'),
                ]);
                Redis::expire($statusKey, 86400);
                Log::info('EPISODE_DUPLICATION_COMPLETED', [
                    'duplication_id' => $newEpisodeUuid,
                    'batch_id' => $batch->id,
                ]);
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($newEpisodeUuid) {
                Log::error('EPISODE_DUPLICATION_FAILED', [
                    'duplication_id' => $newEpisodeUuid,
                    'error' => $e->getMessage(),
                ]);
                CleanupDuplicationJob::dispatch(
                    newEpisodeUuid: $newEpisodeUuid,
                    duplicationId: $newEpisodeUuid,
                );
            })
            ->finally(function () use ($lockKey) {
                app(LockServiceInterface::class)->releaseLock($lockKey);
            })
            ->dispatch();

        $statusKey = LockKeyHelper::getDuplicationStatusKey($newEpisodeUuid);
        Redis::hmset($statusKey, [
            'status' => 'processing',
            'source_episode_uuid' => $originalEpisodeUuid,
            'new_episode_uuid' => $newEpisodeUuid,
            'batch_id' => $batch->id,
            'started_at' => Carbon::now('UTC')->format('Y-m-d H:i:s'),
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
        $data = Redis::hgetall($statusKey);

        if (empty($data)) {
            throw new \RuntimeException('DUPLICATION_NOT_FOUND');
        }

        $totalJobs = 0;
        $pendingJobs = 0;
        $failedJobs = 0;
        $progressPercent = null;

        if (! empty($data['batch_id'])) {
            $batch = Bus::findBatch($data['batch_id']);
            if ($batch) {
                $totalJobs = $batch->totalJobs;
                $pendingJobs = $batch->pendingJobs;
                $failedJobs = $batch->failedJobs;
                $progressPercent = $batch->progress();
            }
        }

        return new StatusResultTo(
            status: $data['status'] ?? 'unknown',
            originalEpisodeUuid: $data['source_episode_uuid'] ?? '',
            newEpisodeUuid: $data['new_episode_uuid'] ?? $duplicationId,
            startedAt: $data['started_at'] ?? null,
            completedAt: $data['completed_at'] ?? null,
            totalJobs: $totalJobs,
            pendingJobs: $pendingJobs,
            failedJobs: $failedJobs,
            progressPercent: $progressPercent,
        );
    }

    public function cancel(string $duplicationId): CancelResultTo
    {
        $statusKey = LockKeyHelper::getDuplicationStatusKey($duplicationId);
        // TODO calling hgetall etc like this is not really nice - extract to interface like RedisServerInterface
        $data = Redis::hgetall($statusKey);

        if (empty($data)) {
            throw new \RuntimeException('DUPLICATION_NOT_FOUND');
        }

        if (! empty($data['batch_id'])) {
            $batch = Bus::findBatch($data['batch_id']);
            $batch?->cancel();
        }

        CleanupDuplicationJob::dispatch(
            newEpisodeUuid: $data['new_episode_uuid'] ?? $duplicationId
        );

        $cancelledAt = Carbon::now('UTC')->format('Y-m-d H:i:s');
        Redis::hmset($statusKey, [
            'status' => 'cancelled',
            'cancelled_at' => $cancelledAt,
        ]);
        Redis::expire($statusKey, 86400);

        $this->logger->info('EPISODE_DUPLICATION_CANCELLED', [
            'duplication_id' => $duplicationId,
        ]);

        return new CancelResultTo(
            originalEpisodeUuid: $data['source_episode_uuid'] ?? '',
            status: 'cancelled',
            cancelledAt: $cancelledAt,
        );
    }
}
