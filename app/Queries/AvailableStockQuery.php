<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReservation;

final readonly class AvailableStockQuery
{
    public function execute(int $productId, int|string|null $variantId = null): int
    {
        $result = $this->executeMany([
            ['product_id' => $productId, 'product_variant_id' => $variantId],
        ]);

        return $result[$this->key($productId, $variantId)] ?? 0;
    }

    /**
     * @param  list<array{product_id: int, product_variant_id?: int|string|null}>  $items
     * @return array<string, int>
     */
    public function executeMany(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $productIds = array_unique(array_column($items, 'product_id'));
        $variantIds = array_values(array_unique(array_filter(
            array_column($items, 'product_variant_id')
        )));

        $stocks = $this->getBaseStocks($variantIds, $items);
        $reserved = $this->getReservedQuantities($productIds);

        $result = [];

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $variantId = $item['product_variant_id'] ?? null;
            $key = $this->key($productId, $variantId);

            $stock = $stocks[$key] ?? 0;
            $reservedQty = $reserved[$key] ?? 0;

            $result[$key] = max(0, $stock - $reservedQty);
        }

        return $result;
    }

    /**
     * @param  array<int|string>  $variantIds
     * @param  list<array{product_id: int, product_variant_id?: int|string|null}>  $items
     * @return array<string, int>
     */
    private function getBaseStocks(array $variantIds, array $items): array
    {
        $stocks = [];

        if ($variantIds !== []) {
            $variantStocks = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->get(['id', 'product_id', 'stock']);

            foreach ($variantStocks as $row) {
                $stocks[$this->key($row->product_id, $row->id)] = (int) $row->stock;
            }
        }

        $productOnlyIds = [];
        foreach ($items as $item) {
            if (empty($item['product_variant_id'])) {
                $productOnlyIds[] = $item['product_id'];
            }
        }

        if ($productOnlyIds !== []) {
            $productStocks = Product::query()
                ->whereIn('id', array_unique($productOnlyIds))
                ->get(['id', 'stock']);

            foreach ($productStocks as $row) {
                $stocks[$this->key($row->id, null)] = (int) $row->stock;
            }
        }

        return $stocks;
    }

    /**
     * @param  array<int>  $productIds
     * @return array<string, int>
     */
    private function getReservedQuantities(array $productIds): array
    {
        $reserved = [];

        $query = StockReservation::query()
            ->where('expires_at', '>', now())
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id', 'product_variant_id')
            ->selectRaw('product_id, product_variant_id, SUM(quantity) as reserved_quantity');

        foreach ($query->get() as $row) {
            $reserved[$this->key($row->product_id, $row->product_variant_id)] = (int) $row->reserved_quantity; // @phpstan-ignore property.notFound
        }

        return $reserved;
    }

    private function key(int $productId, int|string|null $variantId): string
    {
        return $productId . ':' . ($variantId ?? 'null');
    }
}
