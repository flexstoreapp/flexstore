<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;

final readonly class ProductReviewsQuery
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Product $product, int $perPage): array
    {
        return Review::query()
            ->where('product_id', $product->id)
            ->where('status', ReviewStatus::Approved)
            ->with('user:id,name')
            ->select(['id', 'user_id', 'rating', 'title', 'content', 'created_at'])
            ->latest()
            ->paginate($perPage)
            ->through(fn (Review $review): array => [
                'id' => $review->id,
                'rating' => $review->rating,
                'title' => $review->title,
                'content' => $review->content,
                'created_at' => $review->created_at,
                'user' => $review->user === null ? null : ['name' => $review->user->name],
            ])
            ->toArray();
    }
}
