<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdminThemeColor;
use App\Enums\Appearance;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
final class UserFactory extends Factory
{
    private static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->name(),
            'url_handle' => Str::slug($name),
            'email' => fake()->unique()->safeEmail(),
            'password' => self::$password ??= 'password',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'appearance' => Appearance::System,
            'admin_theme_color' => AdminThemeColor::Neutral,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function customer(): static
    {
        return $this->afterCreating(fn (User $user): User => $user->assignRole(Role::Customer));
    }
}
