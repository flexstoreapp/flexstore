<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class StockMovementListQuery
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\StockMovement>
     */
    public function execute(Product|ProductVariant $target, int $perPage = 15): LengthAwarePaginator
    {
        return $target->stockMovements()
            ->with(['user:id,name,email'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
