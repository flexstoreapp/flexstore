<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Country;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Region>
 */
#[UseModel(Region::class)]
final class RegionFactory extends Factory
{
    public function definition(): array
    {
        $country = fake()->randomElement(Country::cases());
        assert($country instanceof Country);

        return [
            'name' => $country->value,
            'countries' => [$country->name],
            'states' => null,
            'postal_codes' => null,
            'is_active' => fake()->boolean(80),
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

    public function withMultipleCountries(int $count = 4): self
    {
        return $this->state(function (array $attributes) use ($count): array {
            $countries = [];

            for ($i = 0; $i < $count; $i++) {
                $countries[] = fake()->unique()->randomElement(Country::cases())->name;
            }

            return [
                'name' => 'Multiple countries',
                'countries' => $countries,
            ];
        });
    }

    public function withStates(int $count = 3): self
    {
        return $this->state(function (array $attributes) use ($count): array {
            $states = [];

            for ($i = 0; $i < $count; $i++) {
                $states[] = fake()->unique()->city();
            }

            return [
                'states' => $states,
            ];
        });
    }

    public function withPostalCodes(int $count = 3): self
    {
        return $this->state(function (array $attributes) use ($count): array {
            $postal_codes = [];

            for ($i = 0; $i < $count; $i++) {
                $postal_codes[] = fake()->unique()->postcode();
            }

            return [
                'postal_codes' => $postal_codes,
            ];
        });
    }
}
