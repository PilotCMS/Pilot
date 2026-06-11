<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\Content;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentReference>
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
