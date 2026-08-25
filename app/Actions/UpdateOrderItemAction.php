<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\HydratedOrderItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;

final readonly class UpdateOrderItemAction
{
    public function handle(OrderItem $orderItem, Product $product, ?ProductVariant $variant, HydratedOrderItem $item): OrderItem
    {
        $orderItem->update([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'media_id' => ($variant->media ?? $product->featured_media)?->id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unitPrice,
            'total_price' => $item->totalPrice,
            'cost_per_item' => $variant->cost_per_item ?? $product->cost_per_item,
            'tax_amount' => '0.0000',
            'variant_options' => $item->variantOptions,
        ]);

        return $orderItem;
    }
}
