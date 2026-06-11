<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentType>
 */
class ContentTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->words(2, true),
            'key' => Str::slug($name),
            'description' => fake()->sentence(),
            'schema' => ['fields' => []],
            'allowed_blocks' => [],
            'settings' => [
                'url_pattern' => '/{slug}',
                'preview_enabled' => true,
            ],
            'is_active' => true,
        ];
    }
}
