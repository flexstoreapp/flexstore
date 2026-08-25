<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Models\Product;
use App\Models\User;
use App\Queries\CanReviewProductQuery;
use App\Queries\ProductDetailQuery;
use App\Queries\ProductDetailSettingsQuery;
use App\Queries\ProductReviewsQuery;
use App\Queries\RelatedProductsQuery;
use App\Utilities\StorefrontHead;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ProductController
{
    public function show(
        Request $request,
        Product $product,
        #[CurrentUser] ?User $user,
        ProductDetailQuery $detailQuery,
        ProductDetailSettingsQuery $settingsQuery,
        CanReviewProductQuery $canReviewQuery,
        ProductReviewsQuery $reviewsQuery,
        RelatedProductsQuery $relatedProductsQuery,
    ): Response {
        abort_unless($product->is_active, 404);

        $settings = $settingsQuery->execute();
        $detail = $detailQuery->execute($product);

        StorefrontHead::product($detail);

        return Inertia::render('storefront/products/show', [
            'product' => $detail,
            'settings' => $settings,
            'canReview' => $canReviewQuery->execute($product, $user),
            ...$settings['show_reviews'] ? [
                'reviews' => Inertia::defer(fn (): array => $reviewsQuery->execute($product, $settings['reviews_per_page'])),
            ] : [],
            ...$settings['show_related_products'] ? [
                'relatedProducts' => Inertia::defer(fn (): array => $relatedProductsQuery->execute($product, $settings['related_products_count'])),
            ] : [],
        ]);
    }
}
