<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\AdjustStockAction;
use App\Http\Requests\Admin\StoreStockAdjustmentRequest;
use App\Http\Resources\Api\V1\Admin\StockMovementResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final readonly class StockAdjustmentController
{
    public function store(
        StoreStockAdjustmentRequest $request,
        #[CurrentUser] User $user,
        AdjustStockAction $action,
    ): JsonResponse {
        $product = Product::query()->findOrFail($request->safe()->integer('product_id'));

        $variantId = $request->safe()->string('product_variant_id')->value();
        $variant = $variantId !== '' ? ProductVariant::query()->findOrFail($variantId) : null;

        try {
            $stockMovement = $action->handle($user, $product, $variant, $request->toDto());
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'product_id' => [__('This product does not track stock, or its stock is managed on its variants.')],
            ]);
        }

        $stockMovement->setRelation('user', $user);

        return StockMovementResource::make($stockMovement)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
