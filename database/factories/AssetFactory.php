<?php

namespace Database\Factories;

use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->slug().'.jpg';

        return [
            'space_id' => Space::factory(),
            'folder_id' => null,
            'disk' => 'public',
            'path' => 'assets/'.$filename,
            'filename' => $filename,
            'display_name' => fake()->words(3, true),
            'description' => null,
            'mime' => 'image/jpeg',
            'size' => fake()->numberBetween(10_000, 2_000_000),
            'width' => 1200,
            'height' => 800,
            'focal_x' => null,
            'focal_y' => null,
            'alt' => null,
            'title' => null,
            'credit' => null,
            'copyright' => null,
            'license' => null,
            'source_url' => null,
            'checksum' => hash('sha256', $filename),
            'expires_at' => null,
            'metadata' => [],
        ];
    }
}
