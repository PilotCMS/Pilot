<?php

namespace Database\Factories;

use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SpacePreviewTarget>
 */
class SpacePreviewTargetFactory extends Factory
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
            'name' => fake()->unique()->randomElement(['Production', 'Staging', 'Local']).' '.fake()->randomNumber(3),
            'url' => fake()->url(),
            'sort_order' => 0,
            'is_default' => false,
        ];
    }
}
