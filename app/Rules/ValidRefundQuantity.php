<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Order;
use App\Queries\RefundableOrderDataQuery;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidRefundQuantity implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @var array<int, int>|null
     */
    private ?array $refundableQuantities = null;

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
            $fail(__('The refund quantity must be greater than zero.'));

            return;
        }

        $orderItemId = $this->extractOrderItemIdFromAttribute($attribute);

        if ($orderItemId === null || $orderItemId === 0) {
            $fail(__('Unable to determine the order item for validation.'));

            return;
        }

        $refundableQuantities = $this->getRefundableQuantities();

        if (! isset($refundableQuantities[$orderItemId])) {
            $fail(__('The selected order item does not exist.'));

            return;
        }

        $availableQuantity = $refundableQuantities[$orderItemId];

        if ($value > $availableQuantity) {
            $fail(__('The refund quantity cannot exceed :quantity.', ['quantity' => $availableQuantity]));
        }
    }

    /**
     * @return array<int, int>
     */
    private function getRefundableQuantities(): array
    {
        if ($this->refundableQuantities === null) {
            $this->refundableQuantities = resolve(RefundableOrderDataQuery::class)
                ->execute($this->order)['refundable_quantities'];
        }

        return $this->refundableQuantities;
    }

    private function extractOrderItemIdFromAttribute(string $attribute): ?int
    {
        // e.g., "items.0.quantity" -> extract index "0" -> get "items.0.order_item_id"
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
