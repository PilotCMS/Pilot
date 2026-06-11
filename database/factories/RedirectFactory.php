<?php

namespace Database\Factories;

use App\Models\Content;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Redirect>
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
