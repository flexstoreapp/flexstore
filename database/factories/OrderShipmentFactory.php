<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\ShippingCarrier;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderShipment>
 */
#[UseModel(OrderShipment::class)]
final class OrderShipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => null,
            'tracking_number' => null,
            'tracking_url' => null,
            'shipped_at' => now(),
        ];
    }

    public function withTracking(): static
    {
        return $this->state(fn (): array => [
            'tracking_number' => fake()->bothify('??########'),
            'tracking_url' => fake()->url(),
        ]);
    }

    public function withCarrier(): static
    {
        return $this->state(fn (): array => [
            'shipping_carrier_id' => ShippingCarrier::factory()->shippo(),
            'tracking_number' => fake()->bothify('??########'),
            'tracking_url' => fake()->url(),
        ]);
    }
}
