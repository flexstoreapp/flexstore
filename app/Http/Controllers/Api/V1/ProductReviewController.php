<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\StoreReviewAction;
use App\Enums\ReviewStatus;
use App\Http\Requests\Storefront\StoreProductReviewRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class ProductReviewController
{
    public function index(Product $product): AnonymousResourceCollection
    {
        abort_unless($product->is_active, 404);

        return ReviewResource::collection(
            Review::query()
                ->select(['id', 'user_id', 'rating', 'title', 'content', 'created_at'])
                ->where('product_id', $product->id)
                ->where('status', ReviewStatus::Approved)
                ->with('user:id,name')
                ->latest()
                ->paginate(),
        );
    }

    public function store(
        StoreProductReviewRequest $request,
        Product $product,
        #[CurrentUser] User $user,
        StoreReviewAction $action,
    ): JsonResponse {
        $action->handle($request->toDto($product, $user));

        return response()->json([
            'message' => __('Thank you for your review!'),
        ], Response::HTTP_CREATED);
    }
}
