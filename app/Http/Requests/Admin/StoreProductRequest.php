<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreProductInput;
use App\Enums\DimensionUnit;
use App\Enums\MediaType;
use App\Enums\ProductType;
use App\Enums\TaxCategory;
use App\Enums\WeightUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Rules\MediaRule;
use App\Rules\ProductStockRule;
use App\Rules\ProductVariantUniqueRule;
use App\Rules\ProductWeightRule;
use App\Rules\ProductWeightUnitRule;
use App\Rules\SlugRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ProductType::class)],
            'url_handle' => ['nullable', 'string', 'max:255', new SlugRule(), Rule::unique(Product::class, 'url_handle')],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', Rule::exists(Category::class, 'id')],
            'brand_id' => ['nullable', Rule::exists(Brand::class, 'id')],
            'tax_category' => ['nullable', 'required_if_declined:is_tax_exempt', Rule::enum(TaxCategory::class)],
            'is_tax_exempt' => ['required', 'boolean'],
            'price' => ['nullable', 'required_without:variants', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'cost_per_item' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique(Product::class, 'sku'), Rule::unique(ProductVariant::class, 'sku')],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique(Product::class, 'barcode'), Rule::unique(ProductVariant::class, 'barcode')],
            'track_stock' => ['nullable', 'required_without:variants', 'boolean'],
            'stock' => new ProductStockRule(),
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'in_stock' => ['nullable', 'required_without:variants', 'boolean'],
            'weight' => new ProductWeightRule(),
            'weight_unit' => new ProductWeightUnitRule(),
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'dimension_unit' => ['nullable', Rule::enum(DimensionUnit::class)],
            'media' => ['nullable', 'array'],
            'media.*' => ['integer', new MediaRule(MediaType::Image)],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'add_more' => ['sometimes', 'boolean'],

            'options' => ['nullable', 'required_with:variants', 'array'],
            'options.*.id' => ['required', 'uuid'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.values' => ['required', 'array', 'min:1'],
            'options.*.values.*.id' => ['required', 'uuid'],
            'options.*.values.*.value' => ['required', 'string', 'max:255'],

            'variants' => ['nullable', 'required_with:options', 'array'],
            'variants.*.id' => ['required', 'uuid'],
            'variants.*.title' => ['required', 'string', 'max:255'],
            'variants.*.options' => ['required', 'array', 'min:1'],
            'variants.*.options.*.option_id' => ['required', 'uuid'],
            'variants.*.options.*.value_id' => ['required', 'uuid'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_per_item' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:255', new ProductVariantUniqueRule('sku', mb_strtolower(__('SKU')))],
            'variants.*.barcode' => ['nullable', 'string', 'max:255', new ProductVariantUniqueRule('barcode', mb_strtolower(__('Barcode')))],
            'variants.*.track_stock' => ['required', 'boolean'],
            'variants.*.stock' => ['nullable', 'required_if_accepted:variants.*.track_stock', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.in_stock' => ['required', 'boolean'],
            'variants.*.weight' => ['nullable', 'required_if:type,physical', 'numeric', 'gt:0'],
            'variants.*.weight_unit' => ['nullable', 'required_if:type,physical', 'string', Rule::enum(WeightUnit::class)],
            'variants.*.length' => ['nullable', 'numeric', 'min:0'],
            'variants.*.width' => ['nullable', 'numeric', 'min:0'],
            'variants.*.height' => ['nullable', 'numeric', 'min:0'],
            'variants.*.dimension_unit' => ['nullable', Rule::enum(DimensionUnit::class)],
            'variants.*.media_id' => ['nullable', 'integer', new MediaRule(MediaType::Image)],
            'variants.*.is_default' => ['required', 'boolean'],
            'downloads' => ['nullable', 'array', 'required_if:type,digital'],
            'downloads.*.id' => ['nullable', 'uuid'],
            'downloads.*.variant_id' => ['nullable', 'uuid'],
            'downloads.*.name' => ['required', 'string', 'max:255'],
            'downloads.*.media_id' => ['required', 'integer', new MediaRule(MediaType::File)],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'title' => mb_strtolower(__('Title')),
            'url_handle' => mb_strtolower(__('URL handle')),
            'description' => mb_strtolower(__('Description')),
            'category_id' => mb_strtolower(__('Category')),
            'brand_id' => mb_strtolower(__('Brand')),
            'tax_category' => mb_strtolower(__('Tax category')),
            'is_tax_exempt' => mb_strtolower(__('Tax exempt')),
            'price' => mb_strtolower(__('Price')),
            'compare_at_price' => mb_strtolower(__('Compare-at price')),
            'cost_per_item' => mb_strtolower(__('Cost per item')),
            'sku' => mb_strtolower(__('SKU')),
            'barcode' => mb_strtolower(__('Barcode')),
            'track_stock' => mb_strtolower(__('Track stock')),
            'stock' => mb_strtolower(__('Stock')),
            'low_stock_threshold' => mb_strtolower(__('Low stock threshold')),
            'is_active' => mb_strtolower(__('Active')),
            'in_stock' => mb_strtolower(__('In stock')),
            'weight' => mb_strtolower(__('Weight')),
            'weight_unit' => mb_strtolower(__('Weight unit')),
            'length' => mb_strtolower(__('Length')),
            'width' => mb_strtolower(__('Width')),
            'height' => mb_strtolower(__('Height')),
            'dimension_unit' => mb_strtolower(__('Dimension unit')),
            'media' => mb_strtolower(__('Media')),
            'media.*' => mb_strtolower(__('Media')),
            'seo_title' => mb_strtolower(__('SEO title')),
            'seo_description' => mb_strtolower(__('SEO description')),
            'add_more' => mb_strtolower(__('Add more')),

            'options' => mb_strtolower(__('Options')),
            'options.*.id' => mb_strtolower(__('Option')),
            'options.*.name' => mb_strtolower(__('Option name')),
            'options.*.values' => mb_strtolower(__('Option values')),
            'options.*.values.*.id' => mb_strtolower(__('Option value')),
            'options.*.values.*.value' => mb_strtolower(__('Option value')),

            'variants' => mb_strtolower(__('Variants')),
            'variants.*.id' => mb_strtolower(__('Variant')),
            'variants.*.is_default' => mb_strtolower(__('Is default')),
            'variants.*.title' => mb_strtolower(__('Title')),
            'variants.*.options' => mb_strtolower(__('Options')),
            'variants.*.options.*.option_id' => mb_strtolower(__('Option')),
            'variants.*.options.*.value_id' => mb_strtolower(__('Option value')),
            'variants.*.price' => mb_strtolower(__('Price')),
            'variants.*.compare_at_price' => mb_strtolower(__('Compare-at price')),
            'variants.*.cost_per_item' => mb_strtolower(__('Cost per item')),
            'variants.*.sku' => mb_strtolower(__('SKU')),
            'variants.*.barcode' => mb_strtolower(__('Barcode')),
            'variants.*.track_stock' => mb_strtolower(__('Track stock')),
            'variants.*.stock' => mb_strtolower(__('Stock')),
            'variants.*.low_stock_threshold' => mb_strtolower(__('Low stock threshold')),
            'variants.*.in_stock' => mb_strtolower(__('In stock')),
            'variants.*.weight' => mb_strtolower(__('Weight')),
            'variants.*.weight_unit' => mb_strtolower(__('Weight unit')),
            'variants.*.length' => mb_strtolower(__('Length')),
            'variants.*.width' => mb_strtolower(__('Width')),
            'variants.*.height' => mb_strtolower(__('Height')),
            'variants.*.dimension_unit' => mb_strtolower(__('Dimension unit')),
            'variants.*.media_id' => mb_strtolower(__('Image')),
            'type' => mb_strtolower(__('Product type')),
            'downloads' => mb_strtolower(__('Downloads')),
            'downloads.*.id' => mb_strtolower(__('Download')),
            'downloads.*.variant_id' => mb_strtolower(__('Variant')),
            'downloads.*.name' => mb_strtolower(__('Download name')),
            'downloads.*.media_id' => mb_strtolower(__('File')),
        ];
    }

    public function toDto(): StoreProductInput
    {
        return StoreProductInput::fromArray($this->validated());
    }
}
