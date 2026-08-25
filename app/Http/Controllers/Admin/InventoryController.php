<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\IndexInventoryRequest;
use App\Http\Requests\Admin\ShowInventoryRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\InventoryListQuery;
use App\Queries\StockMovementListQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryController
{
    public function index(IndexInventoryRequest $request, InventoryListQuery $query): Response
    {
        return Inertia::render('admin/inventory/list', [
            'products' => $query->execute($request->query(), $request->safe()->integer('per_page', 15)),
            'filters' => Inertia::always([
                'query' => $request->validated('query'),
                'low_stock' => $request->safe()->has('low_stock') ? $request->safe()->boolean('low_stock') : null,
                'in_stock' => $request->safe()->has('in_stock') ? $request->safe()->boolean('in_stock') : null,
            ]),
        ]);
    }

    public function show(ShowInventoryRequest $request, Product $product, StockMovementListQuery $stockMovementQuery): Response
    {
        $variantId = $request->validated('variant');
        $variant = $variantId !== null
            ? ProductVariant::query()->whereKey($variantId)->where('product_id', $product->id)->first()
            : null;

        return Inertia::render('admin/inventory/history', [
            'product' => $product->append('is_low_stock'),
            'variant' => $variant?->append('is_low_stock'),
            'stockMovements' => $stockMovementQuery->execute($variant ?? $product, $request->safe()->integer('per_page', 15)),
        ]);
    }
}
