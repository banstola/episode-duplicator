<?php

namespace Database\Seeders;

use App\Models\BlockFields;
use App\Models\Blocks;
use App\Models\Episodes;
use App\Models\Items;
use App\Models\Media;
use App\Models\Parts;
use Illuminate\Database\Seeder;

class EpisodeSeeder extends Seeder
{
    private const int EPISODES = 3;

    private const int PARTS_PER_EPISODE = 2;

    private const int ITEMS_PER_PART = 2;

    private const int BLOCKS_PER_ITEM = 2;

    private const int BLOCK_FIELDS_PER_BLOCK = 2;

    private const int MEDIA_PER_BLOCK = 1;

    public function run(): void
    {
        Episodes::factory(self::EPISODES)
            ->create()
            ->each($this->getCreateEpisodeCallback());
    }

    private function getCreateEpisodeCallback(): \Closure
    {
        return function (Episodes $episode) {
            Parts::factory(self::PARTS_PER_EPISODE)
                ->create(['episode_uuid' => $episode->episode_uuid])
                ->each($this->getCreatePartsCallback());
        };
    }

    private function getCreatePartsCallback(): \Closure
    {
        return function (Parts $part) {
            Items::factory(self::ITEMS_PER_PART)->create([
                'part_uuid' => $part->part_uuid,
            ])->each($this->getCreateItemCallback());
        };

    }

    private function getCreateItemCallback(): \Closure
    {
        return function (Items $item) {
            Blocks::factory(self::BLOCKS_PER_ITEM)->create([
                'item_uuid' => $item->item_uuid,
            ])->each($this->getCreateBlockFieldAndMediaCallback());
        };

    }

    private function getCreateBlockFieldAndMediaCallback(): \Closure
    {
        return function (Blocks $block) {
            BlockFields::factory(self::BLOCK_FIELDS_PER_BLOCK)->create([
                'block_uuid' => $block->block_uuid,
            ]);
            Media::factory(self::MEDIA_PER_BLOCK)->create([
                'block_uuid' => $block->block_uuid,
            ]);
        };

    }
}
