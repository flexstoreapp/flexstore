<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SettingGroup;
use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Setting>
 */
#[UseModel(Setting::class)]
final class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'value' => fake()->sentence(),
            'group' => fake()->randomElement(SettingGroup::cases()),
            'type' => fake()->randomElement(SettingType::cases()),
        ];
    }

    public function text(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SettingType::Text,
            'value' => fake()->sentence(),
        ]);
    }

    public function boolean(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SettingType::Boolean,
            'value' => fake()->boolean(),
        ]);
    }

    public function integer(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SettingType::Integer,
            'value' => fake()->numberBetween(1, 1000),
        ]);
    }

    public function array(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SettingType::Array,
            'value' => fake()->words(3),
        ]);
    }

    public function asset(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SettingType::Asset,
            'value' => fake()->url(),
        ]);
    }
}
