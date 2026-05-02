<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helper\LogEvent;
use App\Models\Episodes;
use App\Models\Parts;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DuplicateEpisodeJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public int $timeout = 3600;

    public function __construct(
        private readonly string $sourceEpisodeUuid,
        private readonly string $newEpisodeUuid,
    ) {
        $this->onQueue('episode-duplication');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            Log::info(LogEvent::DUPLICATE_EPISODE_CANCELLED, [
                'source_episode_uuid' => $this->sourceEpisodeUuid,
            ]);

            return;
        }

        Log::info(LogEvent::DUPLICATE_EPISODE_STARTED, [
            'source_episode_uuid' => $this->sourceEpisodeUuid,
            'new_episode_uuid' => $this->newEpisodeUuid,
        ]);

        $sourceEpisode = Episodes::where(Episodes::EPISODE_UUID, $this->sourceEpisodeUuid)->firstOrFail();

        $newEpisode = new Episodes;
        $newEpisode->{Episodes::EPISODE_UUID} = $this->newEpisodeUuid;
        $newEpisode->{Episodes::TITLE} = $sourceEpisode->title.' (Copy)';
        $newEpisode->{Episodes::STATUS} = 'draft';
        $newEpisode->save();

        $partsDispatched = 0;

        Parts::where(Parts::EPISODE_UUID, $this->sourceEpisodeUuid)
            ->chunkById(100, function ($parts) use (&$partsDispatched) {
                $jobs = [];
                foreach ($parts as $part) {
                    $jobs[] = new DuplicatePartJob(
                        sourcePartUuid: $part->part_uuid,
                        newPartUuid: Str::uuid7()->toString(),
                        newEpisodeUuid: $this->newEpisodeUuid,
                    );
                }
                $this->batch()->add($jobs);
                $partsDispatched += count($jobs);
            }, Parts::PART_UUID);

        Log::info(LogEvent::DUPLICATE_EPISODE_COMPLETED, [
            'source_episode_uuid' => $this->sourceEpisodeUuid,
            'new_episode_uuid' => $this->newEpisodeUuid,
            'parts_dispatched' => $partsDispatched,
        ]);
    }
}
