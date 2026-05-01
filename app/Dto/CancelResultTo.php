<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class CancelResultTo
{
    public function __construct(
        public string $originalEpisodeUuid,
        public string $status,
        public string $cancelledAt,
    ) {
    }

    public function toArray(): array
    {
        return [
          'original_episode_uuid' => $this->originalEpisodeUuid,
          'status' => $this->status,
          'cancelled_at' => $this->cancelledAt,
        ];

    }
}
