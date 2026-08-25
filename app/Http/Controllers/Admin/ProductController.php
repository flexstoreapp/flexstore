<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreProductAction;
use App\Actions\UpdateProductAction;
use App\Concerns\InteractsWithUploadLimits;
use App\Enums\ProductType;
use App\Enums\TaxCategory;
use App\Http\Requests\Admin\IndexAdminProductRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDownload;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use App\Queries\ProductListQuery;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ProductController
{
    use InteractsWithUploadLimits;

    public function index(IndexAdminProductRequest $request, ProductListQuery $query): Response
    {
        return Inertia::render('admin/products/list', [
            'products' => $query->execute($request->query(), $request->safe()->integer('per_page', 15)),
            'filters' => Inertia::always([
                'query' => $request->validated('query'),
                'category' => $request->validated('category'),
                'category_name' => $request->validated('category')
                    ? Category::query()->whereKey($request->validated('category'))->value('name')
                    : null,
                'in_stock' => $request->safe()->has('in_stock') ? $request->safe()->boolean('in_stock') : null,
                'is_active' => $request->safe()->has('is_active') ? $request->safe()->boolean('is_active') : null,
                'sort' => $request->validated('sort', 'created_at'),
                'direction' => $request->validated('direction', 'desc'),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/create', [
            'maxUploadSize' => $this->maxUploadSizeMB(...),
            'taxCategories' => TaxCategory::options(),
            'productTypes' => ProductType::options(),
        ]);
    }

    public function store(StoreProductRequest $request, StoreProductAction $action): RedirectResponse
    {
        $product = $action->handle($request->toDto());

        if ($request->safe()->boolean('add_more')) {
            return back();
        }

        return to_route('admin.products.edit', $product);
    }

    public function edit(Product $product): Response
    {
        $product->load([
            'category:id,name',
            'brand:id,name,image_id',
            'brand.image',
            'mediaGallery',
            'options.values',
            'variants' => fn (Relation $query): Relation => $query->latest(),
            'variants.media',
            'variants.options.option',
            'variants.options.value',
            'downloads.media',
        ]);

        $transformedProduct = [
            ...$product->toArray(),
            'downloads' => $product->downloads->map(fn (ProductDownload $download): array => [
                'id' => $download->id,
                'variant_id' => $download->product_variant_id,
                'name' => $download->name,
                'media_id' => $download->media_id,
                'original_filename' => $download->media->original_filename,
                'file_size' => $download->media->size,
                'mime_type' => $download->media->mime_type,
                'sort_order' => $download->sort_order,
            ]),
            'options' => $product->options->map(fn (ProductOption $option): array => [
                ...$option->toArray(),
                'values' => $option->values->map(fn (ProductOptionValue $value): array => [
                    'id' => $value->id,
                    'value' => $value->getTranslations('value'),
                    'product_option_id' => $value->product_option_id,
                ]),
            ]),
            'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                ...$variant->toArray(),
                'media' => $variant->media,
                'options' => $variant->options->map(fn (ProductVariantOption $variantOption): array => [
                    'option_id' => $variantOption->product_option_id,
                    'value_id' => $variantOption->product_option_value_id,
                    'name' => $variantOption->option->getTranslations('name'),
                    'value' => $variantOption->value->getTranslations('value'),
                ]),
            ]),
        ];

        return Inertia::render('admin/products/edit', [
            'product' => $transformedProduct,
            'maxUploadSize' => $this->maxUploadSizeMB(...),
            'taxCategories' => TaxCategory::options(),
            'productTypes' => ProductType::options(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        $action->handle($product, $request->toDto());

        return back();
    }
}
