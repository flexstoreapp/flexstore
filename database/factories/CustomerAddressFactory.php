<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Address\AddressFieldRules;
use App\Enums\Country;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerAddress>
 */
#[UseModel(CustomerAddress::class)]
final class CustomerAddressFactory extends Factory
{
    public function definition(): array
    {
        $country = fake()->randomElement(Country::codes());

        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'country_code' => $country,
            'phone' => fake()->e164PhoneNumber(),
            'is_default' => false,
            ...$this->localizedFields($country),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    public function forCountry(string $country): static
    {
        return $this->state(fn (array $attributes): array => [
            'country_code' => $country,
            ...$this->localizedFields($country),
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    /**
     * @return array{state: string|null, postal_code: string|null}
     */
    private function localizedFields(string $country): array
    {
        $format = AddressFieldRules::for($country);
        $state = $format['state'];

        return [
            'state' => match (true) {
                $state['hidden'] => null,
                $state['options'] !== null => fake()->randomElement($state['options'])['value'],
                default => fake()->state(),
            },
            'postal_code' => $format['postal_code']['hidden'] ? null : fake()->postcode(),
        ];
    }
}
