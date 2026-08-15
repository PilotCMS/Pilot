<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Space;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->sentence(3);

        return [
            'space_id' => Space::factory(),
            'parent_id' => null,
            'content_type_id' => null,
            'type' => 'page',
            'slug' => Str::slug($name),
            'name' => $name,
            'status' => 'draft',
            'workflow_status' => 'draft',
            'published_at' => null,
            'scheduled_for' => null,
            'reviewer_id' => null,
            'review_due_at' => null,
            'review_note' => null,
            'meta' => [],
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'workflow_status' => 'published',
            'published_at' => now(),
        ]);
    }
}
