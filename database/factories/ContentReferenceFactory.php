<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentReference;

/**
 * @extends Factory<ContentReference>
 */
class ContentReferenceFactory extends Factory
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
            'target_content_id' => Content::factory(),
            'block_id' => Block::factory(),
            'field_key' => fake()->word(),
            'meta' => [],
        ];
    }
}
