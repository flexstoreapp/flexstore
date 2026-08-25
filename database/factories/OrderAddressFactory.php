<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Address\AddressFieldRules;
use App\Enums\Country;
use App\Enums\OrderAddressType;
use App\Models\Order;
use App\Models\OrderAddress;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderAddress>
 */
#[UseModel(OrderAddress::class)]
final class OrderAddressFactory extends Factory
{
    public function definition(): array
    {
        $country = fake()->randomElement(Country::codes());

        return [
            'order_id' => Order::factory(),
            'type' => fake()->randomElement(OrderAddressType::cases()),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'country_code' => $country,
            'phone' => fake()->optional()->e164PhoneNumber(),
            ...$this->localizedFields($country),
        ];
    }

    public function forCountry(string $country): static
    {
        return $this->state(fn (array $attributes): array => [
            'country_code' => $country,
            ...$this->localizedFields($country),
        ]);
    }

    public function billing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => OrderAddressType::Billing,
        ]);
    }

    public function shipping(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => OrderAddressType::Shipping,
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_id' => $order->id,
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
