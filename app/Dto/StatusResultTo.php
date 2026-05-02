<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class StatusResultTo
{
    public function __construct(
        public string $status,
        public string $originalEpisodeUuid,
        public string $newEpisodeUuid,
        public ?string $startedAt,
        public ?string $completedAt,
        public int $totalJobs = 0,
        public int $pendingJobs = 0,
        public int $failedJobs = 0,
        public ?int $progressPercent = null,
    ) {}

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'original_episode_uuid' => $this->originalEpisodeUuid,
            'new_episode_uuid' => $this->newEpisodeUuid,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'total_jobs' => $this->totalJobs,
            'pending_jobs' => $this->pendingJobs,
            'failed_jobs' => $this->failedJobs,
            'progress_percent' => $this->progressPercent,
        ];
    }
}
