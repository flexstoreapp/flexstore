<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateProductInput;
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
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('product')] Product $product): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(ProductType::class)],
            'url_handle' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                new SlugRule(),
                Rule::unique(Product::class, 'url_handle')->ignore($product),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'category_id' => ['sometimes', 'nullable', Rule::exists(Category::class, 'id')],
            'brand_id' => ['sometimes', 'nullable', Rule::exists(Brand::class, 'id')],
            'tax_category' => ['nullable', 'required_if_declined:is_tax_exempt', Rule::enum(TaxCategory::class)],
            'is_tax_exempt' => ['sometimes', 'nullable', 'boolean'],
            'price' => ['sometimes', 'nullable', 'required_without:variants', 'numeric', 'min:0'],
            'compare_at_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cost_per_item' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique(Product::class, 'sku')->ignore($product),
                Rule::unique(ProductVariant::class, 'sku'),
            ],
            'barcode' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique(Product::class, 'barcode')->ignore($product),
                Rule::unique(ProductVariant::class, 'barcode'),
            ],
            'track_stock' => ['sometimes', 'required', 'boolean'],
            'stock' => ['sometimes', new ProductStockRule()],
            'low_stock_threshold' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'in_stock' => ['sometimes', 'nullable', 'required_without:variants', 'boolean'],
            'media' => ['sometimes', 'nullable', 'array'],
            'media.*' => ['integer', new MediaRule(MediaType::Image)],
            'weight' => ['sometimes', new ProductWeightRule()],
            'weight_unit' => ['sometimes', new ProductWeightUnitRule()],
            'length' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'width' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'height' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'dimension_unit' => ['sometimes', 'nullable', Rule::enum(DimensionUnit::class)],
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:70'],
            'seo_description' => ['sometimes', 'nullable', 'string', 'max:160'],

            'options' => ['sometimes', 'nullable', 'array'],
            'options.*.id' => ['required', 'uuid'],
            'options.*.name' => ['sometimes', 'required', 'string', 'max:255'],
            'options.*.values' => ['sometimes', 'required', 'array', 'min:1'],
            'options.*.values.*.id' => ['required', 'uuid'],
            'options.*.values.*.value' => ['sometimes', 'required', 'string', 'max:255'],

            'variants' => ['sometimes', 'nullable', 'array'],
            'variants.*.id' => ['required', 'uuid'],
            'variants.*.title' => ['sometimes', 'required', 'string', 'max:255'],
            'variants.*.options' => ['sometimes', 'required', 'array', 'min:1'],
            'variants.*.options.*.option_id' => ['sometimes', 'required', 'uuid'],
            'variants.*.options.*.value_id' => ['sometimes', 'required', 'uuid'],
            'variants.*.price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'variants.*.cost_per_item' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'variants.*.sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                new ProductVariantUniqueRule('sku', mb_strtolower(__('SKU'))),
            ],
            'variants.*.barcode' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                new ProductVariantUniqueRule('barcode', mb_strtolower(__('Barcode'))),
            ],
            'variants.*.track_stock' => ['sometimes', 'required', 'boolean'],
            'variants.*.stock' => [
                'sometimes',
                'nullable',
                'required_if_accepted:variants.*.track_stock',
                'integer',
                'min:0',
            ],
            'variants.*.low_stock_threshold' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.in_stock' => ['sometimes', 'required', 'boolean'],
            'variants.*.weight' => [
                'sometimes',
                'nullable',
                'required_if:type,physical',
                'numeric',
                'gt:0',
            ],
            'variants.*.weight_unit' => [
                'sometimes',
                'nullable',
                'required_if:type,physical',
                'string',
                Rule::enum(WeightUnit::class),
            ],
            'variants.*.length' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'variants.*.width' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'variants.*.height' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'variants.*.dimension_unit' => ['sometimes', 'nullable', Rule::enum(DimensionUnit::class)],
            'variants.*.media_id' => ['sometimes', 'nullable', 'integer', new MediaRule(MediaType::Image)],
            'variants.*.is_default' => ['sometimes', 'required', 'boolean'],
            'downloads' => $product->downloads()->exists()
                ? ['nullable', 'array']
                : ['nullable', 'array', 'required_if:type,digital'],
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

    public function toDto(): UpdateProductInput
    {
        return UpdateProductInput::fromArray($this->validated());
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        /** @var Product $product */
        $product = $this->route('product');

        $defaults = [];

        if (! $this->has('is_tax_exempt')) {
            $defaults['is_tax_exempt'] = $product->is_tax_exempt;
        }

        if (! $this->has('tax_category')) {
            $defaults['tax_category'] = $product->tax_category?->value;
        }

        if ($defaults !== []) {
            $this->merge($defaults);
        }
    }
}
