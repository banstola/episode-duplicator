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
    ) {}

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'original_episode_uuid' => $this->originalEpisodeUuid,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
        ];

    }
}
