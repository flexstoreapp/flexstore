<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\DTOs\ProductFilterInput;
use App\Queries\StorefrontProductListQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class ProductFilterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'categories' => ['nullable', 'string'],
            'brands' => ['nullable', 'string'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'on_sale' => ['nullable', 'boolean'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', Rule::in(StorefrontProductListQuery::PER_PAGE_OPTIONS)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'query' => mb_strtolower(__('Search query')),
            'categories' => mb_strtolower(__('Categories')),
            'brands' => mb_strtolower(__('Brands')),
            'min_price' => mb_strtolower(__('Min price')),
            'max_price' => mb_strtolower(__('Max price')),
            'in_stock' => mb_strtolower(__('In stock')),
            'on_sale' => mb_strtolower(__('On sale')),
            'rating' => mb_strtolower(__('Rating')),
            'sort' => mb_strtolower(__('Sort')),
            'per_page' => mb_strtolower(__('Per page')),
            'page' => mb_strtolower(__('Page')),
        ];
    }

    public function toDto(): ProductFilterInput
    {
        return ProductFilterInput::fromArray($this->validated());
    }
}
