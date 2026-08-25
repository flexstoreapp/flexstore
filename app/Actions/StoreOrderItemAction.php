<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\HydratedOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;

final readonly class StoreOrderItemAction
{
    public function handle(Order $order, Product $product, ?ProductVariant $variant, HydratedOrderItem $item): OrderItem
    {
        return OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'media_id' => $this->resolveMediaId($product, $variant),
            'product_title' => $item->productTitle,
            'product_sku' => $item->productSku,
            'variant_title' => $item->variantTitle,
            'variant_options' => $item->variantOptions,
            'requires_shipping' => $item->requiresShipping,
            'quantity' => $item->quantity,
            'unit_price' => $item->unitPrice,
            'total_price' => $item->totalPrice,
            'cost_per_item' => $this->resolveUnitCost($product, $variant),
            'tax_amount' => '0.0000',
            'weight' => $item->weight,
            'weight_unit' => $item->weightUnit?->value,
            'length' => $item->length,
            'width' => $item->width,
            'height' => $item->height,
            'dimension_unit' => $item->dimensionUnit?->value,
        ]);
    }

    private function resolveUnitCost(Product $product, ?ProductVariant $variant): ?string
    {
        return $variant->cost_per_item ?? $product->cost_per_item;
    }

    private function resolveMediaId(Product $product, ?ProductVariant $variant): ?int
    {
        return ($variant->media ?? $product->featured_media)?->id;
    }
}
