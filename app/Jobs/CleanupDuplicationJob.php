<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helper\DuplicationRedisField;
use App\Helper\DuplicationStatus;
use App\Helper\LockKeyHelper;
use App\Helper\LogEvent;
use App\Models\BlockFields;
use App\Models\Blocks;
use App\Models\Episodes;
use App\Models\Items;
use App\Models\Media;
use App\Models\Parts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CleanupDuplicationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 3600;

    private const int CHUNK_SIZE = 1000;

    public function __construct(
        private readonly string $episodeUuid,
    ) {
        $this->onQueue('episode-duplication');
    }

    public function handle(): void
    {
        Log::info(LogEvent::CLEANUP_STARTED, [
            'episode_uuid' => $this->episodeUuid,
        ]);

        try {
            $partUuids = Parts::where(Parts::EPISODE_UUID, $this->episodeUuid)->pluck(Parts::PART_UUID);
            $itemUuids = Items::whereIn(Items::PART_UUID, $partUuids)->pluck(Items::ITEM_UUID);
            $blockUuids = Blocks::whereIn(Blocks::ITEM_UUID, $itemUuids)->pluck(Blocks::BLOCK_UUID);

            $deletedMedia = 0;
            while (($count = Media::whereIn(Media::BLOCK_UUID, $blockUuids)->limit(self::CHUNK_SIZE)->delete()) > 0) {
                $deletedMedia += $count;
            }

            $deletedBlockFields = 0;
            while (($count = BlockFields::whereIn(BlockFields::BLOCK_UUID, $blockUuids)->limit(self::CHUNK_SIZE)->delete()) > 0) {
                $deletedBlockFields += $count;
            }

            $deletedBlocks = 0;
            while (($count = Blocks::whereIn(Blocks::ITEM_UUID, $itemUuids)->limit(self::CHUNK_SIZE)->delete()) > 0) {
                $deletedBlocks += $count;
            }

            $deletedItems = 0;
            while (($count = Items::whereIn(Items::PART_UUID, $partUuids)->limit(self::CHUNK_SIZE)->delete()) > 0) {
                $deletedItems += $count;
            }

            $deletedParts = Parts::where(Parts::EPISODE_UUID, $this->episodeUuid)->delete();
            Episodes::where(Episodes::EPISODE_UUID, $this->episodeUuid)->delete();
        } catch (\Throwable $exception) {
            Log::error(LogEvent::CLEANUP_FAILED, [
                'episode_uuid' => $this->episodeUuid,
                'error' => $exception->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $exception;
        }

        $statusKey = LockKeyHelper::getDuplicationStatusKey($this->episodeUuid);
        Redis::hmset($statusKey, [
            DuplicationRedisField::STATUS => DuplicationStatus::FAILED,
            DuplicationRedisField::FAILED_AT => Carbon::now('UTC')->format('Y-m-d H:i:s'),
        ]);

        Redis::expire($statusKey, 86400);

        Log::info(LogEvent::CLEANUP_COMPLETED, [
            'episode_uuid' => $this->episodeUuid,
            'deleted_media' => $deletedMedia,
            'deleted_block_fields' => $deletedBlockFields,
            'deleted_blocks' => $deletedBlocks,
            'deleted_items' => $deletedItems,
            'deleted_parts' => $deletedParts,
        ]);
    }
}
