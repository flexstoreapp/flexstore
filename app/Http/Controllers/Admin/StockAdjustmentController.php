<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\AdjustStockAction;
use App\Http\Requests\Admin\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class StockAdjustmentController
{
    public function store(
        StoreStockAdjustmentRequest $request,
        #[CurrentUser] User $user,
        AdjustStockAction $action,
    ): RedirectResponse {
        $product = Product::query()->findOrFail($request->safe()->integer('product_id'));

        $variantId = $request->safe()->string('product_variant_id')->value();
        $variant = $variantId !== '' ? ProductVariant::query()->findOrFail($variantId) : null;

        $action->handle($user, $product, $variant, $request->toDto());

        return back();
    }
}
