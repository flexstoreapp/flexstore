<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\StoreReviewAction;
use App\Http\Requests\Storefront\StoreProductReviewRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class ProductReviewController
{
    public function store(
        StoreProductReviewRequest $request,
        Product $product,
        #[CurrentUser] User $user,
        StoreReviewAction $action,
    ): JsonResponse {
        $action->handle($request->toDto($product, $user));

        return response()->json(['message' => __('Thank you for your review!')]);
    }
}
