<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
#[UseModel(Announcement::class)]
final class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content' => ['en' => fake()->sentence()],
            'url' => fake()->optional()->url(),
            'is_active' => fake()->boolean(80),
            'sort_order' => fake()->numberBetween(0, 10),
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function scheduled(): self
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addDay(),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->subDay(),
            'ends_at' => now()->subHour(),
        ]);
    }
}
