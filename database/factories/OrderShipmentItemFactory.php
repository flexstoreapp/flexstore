<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderShipmentItem>
 */
#[UseModel(OrderShipmentItem::class)]
final class OrderShipmentItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_shipment_id' => OrderShipment::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
