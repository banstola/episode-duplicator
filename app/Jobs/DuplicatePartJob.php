<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helper\LogEvent;
use App\Models\Items;
use App\Models\Parts;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DuplicatePartJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public int $timeout = 3600;

    public function __construct(
        private readonly string $sourcePartUuid,
        private readonly string $newPartUuid,
        private readonly string $newEpisodeUuid,
    ) {
        $this->onQueue('episode-duplication');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            Log::info(LogEvent::DUPLICATE_PART_CANCELLED, [
                'source_part_uuid' => $this->sourcePartUuid,
            ]);

            return;
        }

        Log::info(LogEvent::DUPLICATE_PART_STARTED, [
            'source_part_uuid' => $this->sourcePartUuid,
            'new_part_uuid' => $this->newPartUuid,
        ]);

        $sourcePart = Parts::where(Parts::PART_UUID, $this->sourcePartUuid)->firstOrFail();

        $newPart = new Parts;
        $newPart->{Parts::PART_UUID} = $this->newPartUuid;
        $newPart->{Parts::TITLE} = $sourcePart->title;
        $newPart->{Parts::EPISODE_UUID} = $this->newEpisodeUuid;
        $newPart->save();

        $itemsDispatched = 0;

        Items::where(Items::PART_UUID, $this->sourcePartUuid)
            ->chunkById(100, function ($items) use (&$itemsDispatched) {
                $jobs = [];
                foreach ($items as $item) {
                    $jobs[] = new DuplicateItemJob(
                        sourceItemUuid: $item->item_uuid,
                        newItemUuid: Str::uuid7()->toString(),
                        newPartUuid: $this->newPartUuid,
                    );
                }
                $this->batch()->add($jobs);
                $itemsDispatched += count($jobs);
            }, Items::ITEM_UUID);

        Log::info(LogEvent::DUPLICATE_PART_COMPLETED, [
            'source_part_uuid' => $this->sourcePartUuid,
            'new_part_uuid' => $this->newPartUuid,
            'items_dispatched' => $itemsDispatched,
        ]);
    }
}
