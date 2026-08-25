<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Order;
use App\Queries\OrderShippedQuantitiesQuery;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class ShippedItemsPreserved implements ValidationRule
{
    public function __construct(
        private Order $order,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $shippedQuantities = resolve(OrderShippedQuantitiesQuery::class)->execute($this->order);

        if ($shippedQuantities === []) {
            return;
        }

        $submittedById = [];

        foreach ($value as $item) {
            if (is_array($item) && isset($item['id'])) {
                $submittedById[(int) $item['id']] = $item;
            }
        }

        foreach ($this->order->items()->get() as $item) {
            $shipped = $shippedQuantities[$item->id] ?? 0;

            if ($shipped === 0) {
                continue;
            }

            $titles = $item->getTranslations('product_title');
            $title = $titles[app()->getLocale()] ?? (reset($titles) ?: '');

            if (! array_key_exists($item->id, $submittedById)) {
                $fail(__('":item" has already been shipped and cannot be removed.', ['item' => $title]));

                continue;
            }

            $submittedQuantity = (int) ($submittedById[$item->id]['quantity'] ?? 0);

            if ($submittedQuantity < $shipped) {
                $fail(__('The quantity for ":item" cannot be reduced below the :count already shipped.', [
                    'item' => $title,
                    'count' => $shipped,
                ]));
            }
        }
    }
}
