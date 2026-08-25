<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductDownload;
use App\Models\ProductVariant;
use App\Queries\AvailableStockQuery;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final readonly class CartItemValidator
{
    public function __construct(private AvailableStockQuery $availableStockQuery)
    {
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @return array{products: Collection<string, Product>, variants: Collection<string, ProductVariant>}
     *
     * @throws ValidationException
     */
    public function validate(Collection $items): array
    {
        $errors = [];

        $productIds = $items->pluck('product_id')->unique()->values()->all();
        $variantIds = $items->pluck('product_variant_id')->filter()->unique()->values()->all();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->with('downloads:id,product_id,product_variant_id,sort_order')
            ->get(['id', 'title', 'price', 'is_active', 'track_stock', 'stock', 'in_stock', 'type'])
            ->keyBy('id');

        $variants = $variantIds === []
            ? collect()
            : ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->lockForUpdate()
                ->get(['id', 'title', 'track_stock', 'stock', 'in_stock'])
                ->keyBy('id');

        $stockItems = [];
        foreach ($items as $item) {
            $stockItems[] = [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
            ];
        }
        $availableStocks = $this->availableStockQuery->executeMany($stockItems);

        foreach ($items as $index => $item) {
            $product = $products->get($item->product_id);
            $variant = $item->product_variant_id ? $variants->get($item->product_variant_id) : null;

            if (! $product) {
                $errors["items.{$index}"] = __('Product is no longer available.');

                continue;
            }

            if (! $product->is_active) {
                $errors["items.{$index}"] = __(':product is no longer available.', [
                    'product' => $product->title,
                ]);

                continue;
            }

            if ($item->product_variant_id && ! $variant) {
                $errors["items.{$index}"] = __('Selected variant for :product is no longer available.', [
                    'product' => $product->title,
                ]);

                continue;
            }

            $target = $variant ?? $product;
            $displayName = $variant ? "{$product->title} ({$variant->title})" : $product->title;

            if ($product->isDigital() && ! $this->hasApplicableDownload($product, $item->product_variant_id)) {
                $errors["items.{$index}"] = __(':product is currently unavailable.', [
                    'product' => $displayName,
                ]);

                continue;
            }

            if ($target->track_stock) {
                $stockKey = $item->product_id . ':' . ($item->product_variant_id ?? 'null');
                $available = $availableStocks[$stockKey] ?? 0;

                if ($available <= 0) {
                    $errors["items.{$index}"] = __(':product is currently out of stock.', [
                        'product' => $displayName,
                    ]);

                    continue;
                }

                if ($available < $item->quantity) {
                    $errors["items.{$index}"] = __('Only :available units of :product are available (requested :requested).', [
                        'available' => $available,
                        'product' => $displayName,
                        'requested' => $item->quantity,
                    ]);
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'products' => $products,
            'variants' => $variants,
        ];
    }

    private function hasApplicableDownload(Product $product, ?string $variantId): bool
    {
        return $product->downloads->contains(
            fn (ProductDownload $download): bool => $download->product_variant_id === null
                || $download->product_variant_id === $variantId,
        );
    }
}
