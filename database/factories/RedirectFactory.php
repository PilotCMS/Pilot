<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Redirect;
use Pilot\Core\Models\Space;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => Space::factory(),
            'content_id' => Content::factory(),
            'source' => '/'.fake()->slug(),
            'destination' => '/'.fake()->slug(),
            'status_code' => 301,
            'is_active' => true,
            'last_hit_at' => null,
            'hit_count' => 0,
        ];
    }
}
