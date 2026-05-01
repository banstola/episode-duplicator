<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class ScheduleResultTo
{
    public function __construct(
        public string $originalEpisodeUuid,
        public string $duplicatedEpisodeUuid,
        public int $totalParts,
        public int $totalItems,
        public int $totalBlocks,
        public int $totalBlockFields,
        public int $totalMedia
    ) {}

    /**
     * This is not the optimal way - a hydrator could be used or spatie/data for example but for this demo stick to this
     */
    public function toArray(): array
    {
        return [
            'original_episode_uuid' => $this->originalEpisodeUuid,
            'duplicate_id' => $this->duplicatedEpisodeUuid,
            'total_parts' => $this->totalParts,
            'total_items' => $this->totalItems,
            'total_blocks' => $this->totalBlocks,
            'total_block_fields' => $this->totalBlockFields,
            'total_media' => $this->totalMedia,

        ];
    }
}
