<?php

declare(strict_types=1);

namespace App\Queries;

use App\DTOs\HydratedOrderItem;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Utilities\OrderUtility;

final readonly class DuplicateOrderSourceQuery
{
    public function __construct(
        private OrderUtility $orderUtility,
    ) {
    }

    public function execute(Order $order): Order
    {
        $order = $order->fresh([
            'customer',
            'items.media:' . Media::displaySelect(),
            'items.product',
            'items.productVariant',
            'billingAddress',
            'shippingAddress',
        ]);
        assert($order instanceof Order);

        $order->setRelation(
            'items',
            $order->items->filter(fn (OrderItem $item): bool => $item->product_id !== null)->values(),
        );

        $this->refreshItemSnapshots($order);
        $this->recalculateSubtotal($order);

        $order->items->each(
            fn (OrderItem $item) => $item->makeHidden(['product', 'productVariant']),
        );

        return $order;
    }

    private function refreshItemSnapshots(Order $order): void
    {
        if ($order->items->isEmpty()) {
            return;
        }

        $itemsData = $order->items->map(fn (OrderItem $item): array => [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity,
            'variant_options' => $item->variant_options,
        ])->all();

        $hydrated = $this->orderUtility->hydrateItems($itemsData);

        foreach ($order->items as $index => $item) {
            $this->applyHydratedItem($item, $hydrated[$index]);
        }
    }

    private function applyHydratedItem(OrderItem $item, HydratedOrderItem $hydrated): void
    {
        $item->setTranslations('product_title', $hydrated->productTitle);
        $item->forceFill([
            'product_sku' => $hydrated->productSku,
            'variant_title' => $hydrated->variantTitle,
            'unit_price' => $hydrated->unitPrice,
            'total_price' => $hydrated->totalPrice,
            'requires_shipping' => $hydrated->requiresShipping,
            'weight' => $hydrated->weight,
            'weight_unit' => $hydrated->weightUnit?->value,
            'variant_options' => $hydrated->variantOptions,
        ]);
    }

    private function recalculateSubtotal(Order $order): void
    {
        $subtotal = OrderUtility::calculateSubtotal($order->items);

        $order->forceFill([
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ]);
    }
}
