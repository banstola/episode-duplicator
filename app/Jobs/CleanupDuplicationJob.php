<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupDuplicationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly string $episodeUuid) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO
    }
}
