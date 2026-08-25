<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Order;
use App\Queries\OrderItemBreakdownQuery;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidShipmentQuantity implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @var array<int, array{unfulfilled: int, refunded: int, total_refunded: int}>|null
     */
    private ?array $itemBreakdown = null;

    public function __construct(
        private readonly Order $order
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value <= 0) {
            $fail(__('The shipment quantity must be greater than zero.'));

            return;
        }

        $orderItemId = $this->extractOrderItemIdFromAttribute($attribute);

        if ($orderItemId === null || $orderItemId === 0) {
            $fail(__('Unable to determine the order item for validation.'));

            return;
        }

        $this->order->loadMissing('items');

        $orderItem = $this->order->items->firstWhere('id', $orderItemId);

        if ($orderItem === null || ! $orderItem->requires_shipping) {
            $fail(__('The selected order item is not eligible for fulfillment.'));

            return;
        }

        $itemBreakdown = $this->getItemBreakdown();

        if (! isset($itemBreakdown[$orderItemId]) || $itemBreakdown[$orderItemId]['unfulfilled'] === 0) {
            $fail(__('The selected order item is not eligible for fulfillment.'));

            return;
        }

        $availableQuantity = $itemBreakdown[$orderItemId]['unfulfilled'];

        if ($value > $availableQuantity) {
            $fail(__('The shipment quantity cannot exceed :quantity.', ['quantity' => $availableQuantity]));
        }
    }

    /**
     * @return array<int, array{unfulfilled: int, refunded: int, total_refunded: int}>
     */
    private function getItemBreakdown(): array
    {
        if ($this->itemBreakdown === null) {
            $this->itemBreakdown = resolve(OrderItemBreakdownQuery::class)
                ->execute($this->order);
        }

        return $this->itemBreakdown;
    }

    private function extractOrderItemIdFromAttribute(string $attribute): ?int
    {
        $parts = explode('.', $attribute);
        if (count($parts) >= 3 && $parts[0] === 'items' && $parts[2] === 'quantity') {
            $index = $parts[1];
            $orderItemIdKey = "items.{$index}.order_item_id";
            $orderItemId = data_get($this->data, $orderItemIdKey);

            return is_numeric($orderItemId) ? (int) $orderItemId : null;
        }

        return null;
    }
}
