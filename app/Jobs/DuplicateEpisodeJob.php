<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DuplicateEpisodeJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public int $timeout = 3600;

    public function __construct(
        private readonly string $sourceEpisodeUuid,
        private readonly string $newEpisodeUuid,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO
    }
}
