<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;

final readonly class DuplicateOrderChangeQuery
{
    /**
     * @return list<array{title: string, type: 'updated'|'removed'}>
     */
    public function execute(Order $order): array
    {
        $order->load(['items.product', 'items.productVariant']);

        $changes = [];
        $seen = [];

        foreach ($order->items as $item) {
            $title = $item->getTranslation('product_title', app()->getLocale());

            if ($item->product_id === null) {
                if (! isset($seen[$title])) {
                    $changes[] = ['title' => $title, 'type' => 'removed'];
                    $seen[$title] = true;
                }

                continue;
            }

            if (isset($seen[$title])) {
                continue;
            }

            if ($item->product && $this->hasItemChanged($item, $item->product, $item->productVariant)) {
                $changes[] = ['title' => $title, 'type' => 'updated'];
                $seen[$title] = true;
            }
        }

        return $changes;
    }

    private function hasItemChanged(OrderItem $item, Product $product, ?ProductVariant $variant): bool
    {
        if ($item->getTranslations('product_title') !== $product->getTranslations('title')) {
            return true;
        }

        $currentSku = $variant->sku ?? $product->sku ?? '';
        if (($item->product_sku ?? '') !== $currentSku) {
            return true;
        }

        $currentPrice = $variant->price ?? $product->price ?? '0.0000';

        return $item->unit_price !== $currentPrice;
    }
}
