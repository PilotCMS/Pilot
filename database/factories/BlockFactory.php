<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\Content;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content_id' => Content::factory(),
            'parent_block_id' => null,
            'reusable_source_block_id' => null,
            'type' => 'hero',
            'reusable_key' => null,
            'reusable_name' => null,
            'position' => 0,
            'data' => [],
        ];
    }
}
