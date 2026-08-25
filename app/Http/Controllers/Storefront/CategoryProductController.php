<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Requests\Storefront\ProductFilterRequest;
use App\Models\Category;
use App\Queries\StorefrontShopDataQuery;
use App\Utilities\StorefrontHead;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CategoryProductController
{
    public function show(ProductFilterRequest $request, Category $category, StorefrontShopDataQuery $query): Response
    {
        if (! $category->is_active) {
            abort(404);
        }

        StorefrontHead::category($category);

        return Inertia::render('storefront/shop/list', [
            ...$query->execute($request->toDto()->withContextCategory($category->id)),
            'context' => fn (): array => [
                'type' => 'category',
                'name' => $category->getTranslations('name'),
                'description' => $category->getTranslations('description'),
            ],
        ]);
    }
}
