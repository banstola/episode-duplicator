<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BlockFields;
use App\Models\Blocks;
use App\Models\Media;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DuplicateBlockJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public int $timeout = 3600;

    public function __construct(
        private readonly string $sourceBlockUuid,
        private readonly string $newBlockUuid,
        private readonly string $newItemUuid,
    ) {
        $this->onQueue('episode-duplication');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $newBlock = new Blocks;
        $newBlock->{Blocks::BLOCK_UUID} = $this->newBlockUuid;
        $newBlock->{Blocks::ITEM_UUID} = $this->newItemUuid;
        $newBlock->save();

        $this->duplicateBlockFields();
        $this->duplicateMedia();
    }

    private function duplicateBlockFields(): void
    {
        BlockFields::where(BlockFields::BLOCK_UUID, $this->sourceBlockUuid)
            ->chunkById(500, function ($fields) {
                $now = Carbon::now();
                $rows = [];
                foreach ($fields as $field) {
                    $rows[] = [
                        BlockFields::BLOCK_FIELD_UUID => Str::uuid7()->toString(),
                        BlockFields::BLOCK_UUID => $this->newBlockUuid,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                BlockFields::insert($rows);
            }, BlockFields::BLOCK_FIELD_UUID);
    }

    private function duplicateMedia(): void
    {
        Media::where(Media::BLOCK_UUID, $this->sourceBlockUuid)
            ->chunkById(500, function ($mediaRecords) {
                $now = Carbon::now();
                $rows = [];
                foreach ($mediaRecords as $media) {
                    $rows[] = [
                        Media::MEDIA_UUID => Str::uuid7()->toString(),
                        Media::BLOCK_UUID => $this->newBlockUuid,
                        Media::LOCATION => $media->location,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                Media::insert($rows);
            }, Media::MEDIA_UUID);
    }
}
