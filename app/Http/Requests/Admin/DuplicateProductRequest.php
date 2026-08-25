<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\DuplicateProductInput;
use App\Models\Product;
use App\Rules\SlugRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class DuplicateProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'url_handle' => ['nullable', 'string', 'max:255', new SlugRule(), Rule::unique(Product::class, 'url_handle')],
            'is_active' => ['sometimes', 'boolean'],
            'duplicate_media' => ['sometimes', 'boolean'],
            'duplicate_skus' => ['sometimes', 'boolean'],
            'duplicate_barcodes' => ['sometimes', 'boolean'],
            'duplicate_inventory' => ['sometimes', 'boolean'],
            'duplicate_pricing' => ['sometimes', 'boolean'],
            'duplicate_shipping' => ['sometimes', 'boolean'],
            'duplicate_tax' => ['sometimes', 'boolean'],
            'duplicate_seo' => ['sometimes', 'boolean'],
            'duplicate_digital_files' => ['sometimes', 'boolean'],
            'duplicate_category' => ['sometimes', 'boolean'],
            'duplicate_brand' => ['sometimes', 'boolean'],
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
            'is_active' => mb_strtolower(__('Active')),
            'duplicate_media' => mb_strtolower(__('Duplicate media')),
            'duplicate_skus' => mb_strtolower(__('Duplicate SKUs')),
            'duplicate_barcodes' => mb_strtolower(__('Duplicate barcodes')),
            'duplicate_inventory' => mb_strtolower(__('Duplicate inventory')),
            'duplicate_pricing' => mb_strtolower(__('Duplicate pricing')),
            'duplicate_shipping' => mb_strtolower(__('Duplicate shipping')),
            'duplicate_tax' => mb_strtolower(__('Duplicate tax')),
            'duplicate_seo' => mb_strtolower(__('Duplicate SEO')),
            'duplicate_digital_files' => mb_strtolower(__('Duplicate digital files')),
            'duplicate_category' => mb_strtolower(__('Duplicate category')),
            'duplicate_brand' => mb_strtolower(__('Duplicate brand')),
        ];
    }

    public function toDto(): DuplicateProductInput
    {
        return DuplicateProductInput::fromArray($this->validated());
    }
}
