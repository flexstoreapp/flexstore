<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use App\Enums\ListLoadingMethod;
use App\Enums\ProductSortOption;
use App\Queries\StorefrontProductListQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateStorefrontProductListSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'storefront_product_list_loading_method' => [
                'sometimes',
                Rule::enum(ListLoadingMethod::class),
            ],
            'storefront_product_list_default_per_page' => [
                'sometimes',
                'integer',
                Rule::in(StorefrontProductListQuery::PER_PAGE_OPTIONS),
            ],
            'storefront_product_list_default_sort' => [
                'sometimes',
                Rule::enum(ProductSortOption::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'storefront_product_list_loading_method' => mb_strtolower(__('Loading method')),
            'storefront_product_list_default_per_page' => mb_strtolower(__('Default per page')),
            'storefront_product_list_default_sort' => mb_strtolower(__('Default sort')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
