<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Blocks;
use App\Models\Items;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class DuplicateItemJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public int $timeout = 3600;

    public function __construct(
        private readonly string $sourceItemUuid,
        private readonly string $newItemUuid,
        private readonly string $newPartUuid,
    ) {
        $this->onQueue('episode-duplication');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $newItem = new Items;
        $newItem->{Items::ITEM_UUID} = $this->newItemUuid;
        $newItem->{Items::PART_UUID} = $this->newPartUuid;
        $newItem->save();

        Blocks::where(Blocks::ITEM_UUID, $this->sourceItemUuid)
            ->chunkById(100, function ($blocks) {
                $jobs = [];
                foreach ($blocks as $block) {
                    $jobs[] = new DuplicateBlockJob(
                        sourceBlockUuid: $block->block_uuid,
                        newBlockUuid: Str::uuid7()->toString(),
                        newItemUuid: $this->newItemUuid,
                    );
                }
                $this->batch()->add($jobs);
            }, Blocks::BLOCK_UUID);
    }
}
